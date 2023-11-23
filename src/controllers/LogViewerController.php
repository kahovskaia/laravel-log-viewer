<?php

namespace Kahovskaia\LaravelLogViewer;

use Illuminate\Support\Facades\Crypt;
use Lcobucci\JWT\Exception;
use Illuminate\Routing\Controller;

class LogViewerController extends Controller
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     */
    private $file;

    private $logViewer;

    private $page = 1;

    private $perPage = 25;

    public function __construct()
    {
        $this->request = request();
        if ($this->request->has('folder')) {
            $this->folder = Crypt::decrypt($this->request->folder);
        } else {
            $this->folder = storage_path('logs');
        }

        if ($this->request->has('file')) {
            $this->file = Crypt::decrypt($this->request->file);
        } else {
            $this->file = storage_path('logs/laravel.log');
        }

        if ($this->request->has('page')) {
            $this->page = $this->request->page;
        }

        $this->logViewer = new LogViewer();
    }

    public function getCurrentFolder()
    {
        return $this->folder ?? storage_path('/logs');
    }

    public function getCurrentFile()
    {
        return $this->file;
    }

    public function index()
    {
        try {
            $file = file_get_contents($this->file);
            $pattern = "/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:[\+-]\d{4})?\].*/";
            $logs = [];

            preg_match_all($pattern, $file, $headings);

            if (!is_array($headings)) {
                return $logs;
            }
            $log_data = preg_split($pattern, $file);

            if ($log_data[0] < 1) {
                array_shift($log_data);
                $log_data = array_reverse($log_data);
            }
            $headings = array_reverse($headings[0]);

            $totalItems = count($headings);
            $totalPages = ceil($totalItems / $this->perPage);
            // Проверка, чтобы убедиться, что текущая страница в допустимом диапазоне
            if ($this->page < 1 || ($this->page > $totalPages && $this->page != 1)) {
                abort(404);
            } else {
                $startIndex = ($this->page - 1) * $this->perPage;
                $endIndex = min($startIndex + $this->perPage - 1, $totalItems - 1);

                for ($key = $startIndex; $key <= $endIndex; $key++) {
                    $heading = $headings[$key];
                    $level = '';

                    array_map(function ($item) use ($heading, &$level) {
                        if (strpos($heading, '.' . $item) || strpos(strtolower($heading), $item . ':')) {
                            return $level = $item;
                        }
                    }, $this->logViewer->all());

                    preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?)\](?:.*?(\w+)\.|.*?)' . $level . ': (.*?)( in .*?:[0-9]+)?$/i', $heading, $current);
                    if (!isset($current[4])) {
                        continue;
                    }

                    $logs[] = array(
                        'context' => $current[3],
                        'level' => $level,
                        'level_class' => $this->logViewer->getCssClass($level),
                        'level_img' => $this->logViewer->getImg($level),
                        'date' => $current[1],
                        'text' => $current[4],
                        'in_file' => $current[5] ?? null,
                        'stack' => ltrim($log_data[$key], "\n"),
                    );
                }
            }
        } catch (Exception $exception) {
            $lines = explode(PHP_EOL, $file);
            $logs = [];

            foreach ($lines as $key => $line) {
                $logs[] = [
                    'context' => '',
                    'level' => '',
                    'level_class' => '',
                    'level_img' => '',
                    'date' => $key + 1,
                    'text' => $line,
                    'in_file' => null,
                    'stack' => '',
                ];
            }
        }
        $folders = $this->foldersInPath();
        $files = $this->filesInPath();
        $currentFolder = $this->getCurrentFolder();
        $breadcrumbs = $this->getBreadcrumbs();

        return view('index', compact('logs', 'files', 'folders', 'currentFolder', 'breadcrumbs'));
    }

    public function download()
    {
        return response()->download($this->file);
    }

    public function clear()
    {
        $date = date('Y-m-d H:i:s');
        file_put_contents($this->file, "[$date] local.notice: Clear");
        return redirect()->back();
    }

    public function delete()
    {
        unlink($this->file);
        return redirect()->back();
    }

    private function filesInPath()
    {
        $filesAndFolders = array_diff(scandir($this->folder), $this->logViewer->getFilesIgnore());
        $files = array_map(function ($item) {
            return is_file($this->folder . "/$item") ? $item : null;
        }, $filesAndFolders);
        $result = [];
        foreach (array_filter($files) as $file) {
            $url = Crypt::encrypt($this->getCurrentFolder() . "/$file");
            $result[$file] = $url;
        }

        return $result;
    }

    private function foldersInPath()
    {
        $filesAndFolders = array_diff(scandir($this->folder), $this->logViewer->getFilesIgnore());
        $folders = array_map(function ($item) {
            return is_dir($this->folder . "/$item") ? $item : null;
        }, $filesAndFolders);
        $result = [];
        foreach (array_filter($folders) as $folder) {
            $url = Crypt::encrypt($this->getCurrentFolder() . "/$folder");
            $result[$folder] = $url;
        }

        return array_filter($result);
    }

    private function getBreadcrumbs()
    {
        $foldersInString = str_replace(storage_path('/'), '', $this->getCurrentFolder());
        $foldersArray = explode('/', $foldersInString);
        $result = [];
        $folderBeforeInString = '';
        foreach ($foldersArray as $key => $folder) {
            $folderBeforeInString .= "/{$folder}";
            $result[$folder] = Crypt::encrypt(storage_path($folderBeforeInString));
        }

        return $result;
    }
}
