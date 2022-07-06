<?php

namespace Yuki12321\ReplaceString\Commands;

use Illuminate\Console\Command;

class ReplaceStringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'replace:string
    {subjectFilePath : Path after base_path of the target file to replace strings.}
    {configFilePath? : Path after base_path of the configuration file describing the strings to be searched/replaced.};
    {suspendString? : String to suspend the replace operation.};
    {resumeString? : String to restart the replace operation..}
    {--b|needBackup : Leave a backup of the file(.bak) before string replacement.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to replace the contents of a specified file with a specific strings.';

    /**
     * 指定のファイルの中身を特定の文字に置換するコマンド
     *
     * @return int
     */
    public function handle()
    {
        $configFilePath = base_path($this->argument('configFilePath') ?? env('REPLACE_STRING_COMMAND_CONFIG_FILE_RELATIVE_PATH'));
        if (!$configFilePath) {
            $this->error('Setting file path not specified.');
            return 1;
        }

        // Set if replacement needs to be suspended
        $enableSuspendReplaceMode = false;
        $isSuspendingReplace = false;
        $suspendString = $this->argument('suspendString') ?? env('REPLACE_STRING_COMMAND_SUSPEND_STRING');
        $resumeString = $this->argument('resumeString') ?? env('REPLACE_STRING_COMMAND_RESUME_STRING');

        if (!empty($suspendString) && !empty($resumeString) && $suspendString === $resumeString) {
            $this->error('The same value is set for `suspendString` and `resumeString`.');
            return 1;
        } else if ($suspendString && $resumeString) {
            $enableSuspendReplaceMode = true;
        }

        // Get the string to search/replace from the configuration file
        $configFile = file_get_contents($configFilePath);
        if (!$configFile) {
            $this->error('Config file not exist.');
            return 1;
        }
        $configFile = mb_convert_encoding($configFile, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        $searchAndReplaceKeywords = json_decode($configFile, true);

        // Set file to be processed.
        $subjectFilePath = base_path($this->argument('subjectFilePath'));
        if (!file_get_contents($subjectFilePath) && is_file($subjectFilePath) && is_writable($subjectFilePath)) {
            $this->error('File not exist.');
            return 1;
        }

        // Create backup of subject file
        if ($this->option('needBackup')) {
            file_put_contents("$subjectFilePath.bak", file_get_contents($subjectFilePath));
        }

        // Create a file to contain the results of the replacement process
        $tmpFilePath = "$subjectFilePath.tmp";
        file_put_contents($tmpFilePath, "");
        $tfh = fopen($tmpFilePath, "a");

        // Replacing process
        if ($enableSuspendReplaceMode) {
            if ($fh = fopen($subjectFilePath, "r")) {
                while(!feof($fh)) {
                    $line = fgets($fh);

                        $startPos = strrpos($line, $suspendString);
                        $endPos = strrpos($line, $resumeString);

                        // Case by only `suspendString` exists -> isSuspendingReplace: true
                        if ($startPos !== false && $endPos === false) {
                            $isSuspendingReplace = true;
                        // Case by only `resumeString` exist -> isSuspendingReplace: false
                        } elseif ($startPos === false && $endPos !== false) {
                            $isSuspendingReplace = false;
                        // Case by both exist
                        } elseif ($startPos !== false && $endPos !== false) {
                            if ($startPos > $endPos) {
                                // Case by `suspendString` is the last existing -> isSuspendingReplace: true
                                $isSuspendingReplace = true;
                            } elseif ($startPos < $endPos) {
                                // Case by `resumeString` is the last existing -> isSuspendingReplace: false
                                $isSuspendingReplace = false;
                            } elseif ($startPos === $endPos) {
                                // Case by `suspendString` and `resumeString` has the same position(same value?) of the last existing -> isSuspendingReplace: false
                                $isSuspendingReplace = false;
                            }
                        } // Case by neither exists -> isSuspendingReplace: no change

                    $replacedLine = $isSuspendingReplace ? $line : str_replace(array_keys($searchAndReplaceKeywords), array_values($searchAndReplaceKeywords), $line);
                    fputs($tfh, $replacedLine);
                }
                fclose($fh);
            }
        } else {
            file_put_contents($tmpFilePath,  str_replace(array_keys($searchAndReplaceKeywords), array_values($searchAndReplaceKeywords), file_get_contents($subjectFilePath)));
        }

        unlink($subjectFilePath);
        rename($tmpFilePath, $subjectFilePath);

        $this->info('replace:string complete');
        return 0;
    }
}
