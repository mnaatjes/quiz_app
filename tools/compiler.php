<?php
    /**
     * @file PHP TXT to CSV Compiler
     * @since 1.0: Created
     * @since 2.0: Updated
     * @version 2.0
     */

use PharIo\Executor\Executor;

    /**
     * Require PHP Manager
     */
    require_once("../../../packages/php_http_manager/src/main.php");
    // Enable Errors
    ini_errors_enable();
    /*----------------------------------------------------------*/
    /**
     * Create UID
     * @param string $category
     * @param int $i
     * @param int $length (default = 4)
     * @return string
     */
    /*----------------------------------------------------------*/
    function create_uid($category, $i, $length=4){
        return strtoupper($category) . "_" . substr(bin2hex(random_bytes(16)), 0, $length) . "_" . str_pad($i, 4, 0, STR_PAD_LEFT);
    }
    
    /*----------------------------------------------------------*/
    /**
     * CONSTANTS
     */
    /*----------------------------------------------------------*/
    define('FP_WORKING_DIR', '../src/data/raw/');
    define('FP_INPUT', FP_WORKING_DIR . 'txt/');
    define('FP_OUTPUT', FP_WORKING_DIR . 'csv/');

    /*----------------------------------------------------------*/
    /**
     * Read Text Content
     * @param string $input_filename Includes extension, e.g. animals.txt
     * 
     * @return array $lines array of lines
     */
    /*----------------------------------------------------------*/
    function read_txt($input_filename){
        /**
         * @var string $input_filepath Filepath (with working directory and extension) of source text document
         */
        $input_filepath = FP_INPUT . $input_filename;

        /**
         * Read text document and grab content
         * - Validate file exists
         * - Open file
         * - Grab Contents
         * - Grab array of content lines
         */
        if(!file_exists($input_filepath)){
            throw new Exception("Error: File '{$input_filepath}' does not exist!");
        }
        // Open File
        $contents = file_get_contents($input_filepath);
        // Validate Contents
        if($contents === false){
            throw new Exception("Error: Could not read '{$input_filename}");
        }
        /**
         * @var array $lines Array of extracted lines from $contents
         */
        $lines = explode(PHP_EOL, $contents);

        /**
         * Validate and return
         */
        if(is_array($lines)){
            return $lines;
        } else {
            throw new Exception("Error: Unable to parse data from '{$input_filepath}'");
        }
    }

    /*----------------------------------------------------------*/
    /**
     * Parse text to array
     * @param array $lines Array of lines from text document content
     * @param string $category
     * 
     * @return array Assoc array for Questions and Options
     */
    /*----------------------------------------------------------*/
    function txt_contents_to_assoc($lines, $category){
        
        // Define Properties

        /**
         * @var array $data Data array of questions and options arrays
         */
        $data = [
            "questions" => [],
            "options"   => []
        ];

        /**
         * @var array $current Questions and Options current record
         */
        $current = [
            "question"  => [],
            "options"   => []
        ];

        /**
         * @var array $counter Counter for questions, options
         */
        $counter = [
            "question"  => 1,
            "option"    => 1,
            "index"     => 0
        ];

        $eol = 1;
        /**
         * @var array $flag Flag for reaching end of question / options
         */
        $flags = [
            "has_question"      => false,
            "has_answer"        => false,
            "complete"          => false,
            "has_bool"          => false,
            "last_option"       => false
        ];

        /**
         * Loop lines
         */
        foreach($lines as $line){
            /**
             * Trim line and validate
             */
            $line = trim($line);
            if(empty($line)){
                $counter["index"]++;
                // Skip
                continue;
            }

            /**
             * @var string $QID Define question id
             */
            $QID = create_uid($category, $counter["question"]);

            /**
             * Evaluate Line Start
             */
            if(str_starts_with($line, "#Q")){
                /**
                 * Define Question and QID
                 */
                $current["question"]["question"] = trim(substr($line, 2));
                $current["question"]["QID"]      = $QID;
                // Set question flag
                $flags["has_question"] = true;

            } elseif (str_starts_with($line, "^")){
                /**
                 * Define answer and flag answer type
                 */
                $answer = trim(substr($line, 1));
                // Assign current props
                $current["question"]["answer"]      = $answer;
                $current["question"]["answer_OID"]  = null;
                // Set flags
                $flags["has_answer"] = true;
                // Evaluate question type flag
                $flags["has_bool"] = in_array(strtolower($answer), ["yes", "no", "true", "false"]);

            } elseif(preg_match('/^([A-D])\s(.+)/', $line, $matches) && $flags["has_answer"] === true){
                /**
                 * Validate line is an option and not a part of the question line
                 */

                /**
                 * Assign Current Option Properties
                 */
                $current["options"][] = [
                    "OID"   => create_uid($category . "_opt", $matches[1]),
                    "QID"   => $current["question"]["QID"],
                    "text"  => trim($matches[2])
                ];
                /**
                 * Evaluate and flag
                 * - Increment
                 * - Check bool
                 * - Check for last
                 */
                // Increment options counter
                // Check for last option
                if($counter["option"] == 2 && $flags["has_bool"] === true){
                    $flags["last_option"] = true;
                } elseif($counter["option"] == 4 && $flags["has_bool"] === false){
                    $flags["last_option"] = true;
                } else {
                    $counter["option"]++;
                }
            } else {
                /**
                 * Question with second line
                 */
                if(isset($current["question"]["question"])){
                    $current["question"]["question"] .= " " . $line;
                } else {
                    continue;
                }
            }

            /**
             * Evaluate End of Record
             */
            if($flags["last_option"] === true){
                /**
                 * Perform Full Test
                 */
                if($flags["has_question"] && $flags["has_answer"]){
                    /**
                     * Grab Answer OID
                     */
                    $answer         = $current["question"]["answer"];
                    $matching_entry = array_filter($current["options"], function($ele) use($answer){
                        return $ele["text"] === $answer;
                    });
                    if(!isset(array_column($matching_entry, "OID")[0])){
                        var_dump($counter["index"]);
                        var_dump($line);
                        var_dump($category);
                    }
                    $answer_OID = array_column($matching_entry, "OID")[0];
                    $current["question"]["answer_OID"] = $answer_OID;
                    /**
                     * Store data
                     */
                    $data["questions"][] = $current["question"];
                    $data["options"]     = array_merge($data["options"], $current["options"]);
                    /**
                     * Clear current data
                     */
                    $current["question"]    = [];
                    $current["options"]     = [];
                    /**
                     * Reset Option Counter
                     */
                    $counter["option"]   = 1;
                    /**
                     * Advance Question Counter
                     */
                    $counter["question"]++;
                    /**
                     * Reset Flags
                     */
                    foreach($flags as &$flag){
                        $flag = false;
                    }
                }
            }
            /**
             * Increment Counters
             */
            $counter["index"]++;
        }
        return $data;
    }

    /*----------------------------------------------------------*/
    /**
     * Write CSV from Array of Assoc Arrays
     * @param string $category
     * @param array $data array of assoc arrays 
     * 
     * @return bool failure or success
     */
    /*----------------------------------------------------------*/
    function write_csv($category, $data){
        /**
         * Define results flags
         */
        $results = [
            "questions" => false,
            "options"   => false
        ];
        /**
         * CSV Formatting Properties
         */
        $delimiter  = ',';
        $enclosure  = '"';
        $escape     = '\\';
        /**
         * Generate output names
         */
        $file_id = $category . "_" . substr(bin2hex(random_bytes(16)), 0, 4);
        $fp_output_questions = FP_OUTPUT . $file_id . "_questions";
        $fp_output_options   = FP_OUTPUT . $file_id . "_options";
        /**
         * Cycle each data array
         */
        foreach($data as $key => $arr){
            /**
             * Assign File Output Path
             */
           if($key === "questions"){
            $fp_output = $fp_output_questions;
           } else {
            $fp_output = $fp_output_options;
           }
           /**
            * Create and open file for writing
            */
            $handler = fopen($fp_output . ".csv", "w");
            // Validate Handler
            if($handler !== false){
                // get headers
                $headers = array_keys($arr[0]);
                // write headers to file
                if(fputcsv($handler, $headers, $delimiter, $enclosure, $escape) === false){
                    // on failure of header write
                    fclose($handler);
                } else {
                    // Loop data and write
                    $row_number = 0;
                    foreach($arr as $record){
                        // get values
                        $values = array_values($record);
                        // Write to csv
                        if(fputcsv($handler, $values, $delimiter, $enclosure, $escape) === false){
                            throw new Exception("Error! Unable to write file!");
                        }
                        // increment row number
                        $row_number++;
                    }
                    /**
                     * Set flag
                     */
                    $results[strtolower($key)] = true;
                    // Close file
                    fclose($handler);
                }
            }
        }
        var_dump("Category {$category} Completed!");
        /**
         * Return Results
         */
        return $results;
    }
    /**
     * Init $app
     */
    header("Content-Type: application/json");
    $app = new HttpManager();
    $app->get('/', function($req, $res){
        /**
         * Set flag
         */
        $flags = [];
        /**
         * Open directory and list filenames
         */
        $files = scandir(FP_INPUT);
        // Remove empty refs
        $files = array_diff($files, [".", ".."]);
        /**
         * Loop and extract categories
         */
        $categories = [];
        foreach($files as $file){
            $categories[] = trim(pathinfo($file, PATHINFO_FILENAME));
        }

        /**
         * Perform File Write
         */
        $index = 0;
        foreach($categories as $category){
            /**
             * Read text doc and extract array
             */
            $lines = read_txt($category . ".txt");
            /**
             * Generate data array
             */
            $data = txt_contents_to_assoc($lines, $category);
            /**
             * Write to csv file
             */
            $results = write_csv($category, $data);
            /**
             * Evaluate Flag and Assign Body Message
             */
            if($results["questions"] !== true || $results["options"] !== true){
                throw new Exception("Error authoring file!");
            }
            $index++;
            /*
            if($index > 0){
                break;
            }
            */
        }
        /**
         * Send Output Response
         */
        $res->setContentType("application/json");
        $res->setStatus(200);
        $res->setBody("Success");
        $res->send();
    });
    /**
     * Dispatch all routes
     */
    $app->execute();
?>