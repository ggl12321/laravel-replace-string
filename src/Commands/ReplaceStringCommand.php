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
     * A command that replaces the contents of a specified file with a specific character.
     *
     * @return int
     */
    public function handle()
    {
        /* Get Arguments */
        // subjectFile
        $subjectFilePath = base_path($this->argument('subjectFilePath'));
        // configFile
        $configFilePath = base_path($this->argument('configFilePath') ?? env('REPLACE_STRING_COMMAND_CONFIG_FILE_RELATIVE_PATH'));
        // suspendString, resumeString
        $suspendString = $this->argument('suspendString') ?? env('REPLACE_STRING_COMMAND_SUSPEND_STRING');
        $resumeString = $this->argument('resumeString') ?? env('REPLACE_STRING_COMMAND_RESUME_STRING');

        /* Validation */
       // subjectFile
        if (!$subjectFilePath) {
            $this->error('Subject file path not specified.');
            return 1;
        } elseif (!file_exists($subjectFilePath) || !is_file($subjectFilePath) || !is_readable($subjectFilePath)) {
            $this->error("File does not exist. : {$subjectFilePath}");
            return 1;
        }
        // configFile
        if (!$configFilePath) {
            $this->error('Config file path not specified.');
            return 1;
        } elseif (!file_exists($configFilePath) || !is_file($configFilePath) || !is_readable($configFilePath)) {
            $this->error("File does not exist. : {$configFilePath}");
            return 1;
        }
        // suspendString, resumeString
        if (!empty($suspendString) && !empty($resumeString) && $suspendString === $resumeString) {
            $this->error('The same value is set for `suspendString` and `resumeString`.');
            return 1;
        }

        /* Get, Initialize, Create */
        // Get configFile
        $configFile = file_get_contents($configFilePath);
        $configFile = mb_convert_encoding($configFile, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        $searchAndReplaceKeywords = json_decode($configFile, true);

        // Initialize suspend and resume
        $enableSuspendReplaceMode = false;
        $isSuspendingReplace = false;
        if ($suspendString && $resumeString) {
            $enableSuspendReplaceMode = true;
        }

        // Create backup file of subjectFile
        if ($this->option('needBackup')) {
            file_put_contents("$subjectFilePath.bak", file_get_contents($subjectFilePath));
        }

        // Create a file to store the results
        $resultFilePath = "$subjectFilePath.tmp";
        file_put_contents($resultFilePath, "");
        $tfh = fopen($resultFilePath, "a");

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
            file_put_contents($resultFilePath,  str_replace(array_keys($searchAndReplaceKeywords), array_values($searchAndReplaceKeywords), file_get_contents($subjectFilePath)));
        }

        /* Put file */
        unlink($subjectFilePath);
        rename($resultFilePath, $subjectFilePath);

        $this->info('replace:string complete');
        return 0;
    }
}
