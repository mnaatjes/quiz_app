<?php
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
        $input_filepath = FP_WORKING_DIR . $input_filename;

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
            "question"  => null,
            "options"   => null
        ];

        /**
         * @var array $counter Counter for questions, options
         */
        $counter = [
            "question"  => 1,
            "option"    => 1
        ];

        /**
         * @var bool $flag Flag for reaching end of question / options
         */
        $flag = false;

        /**
         * Loop lines
         */
        foreach($lines as $line){
            /**
             * Trim line and validate
             */
            $line = trim($line);
            if(empty($line)){
                continue;
            }

            /**
             * @var string $QID Define question id
             */
            $QID = create_uid($category, $counter["question"]);

            /**
             * Increment Counters
             */
            $counter["question"]++;

        }
    }
    /**
     * Init $app
     */
    header("Content-Type: application/json");
    $app = new HttpManager();
    $app->get('/', function($req, $res){
        $current    = null;
        $options    = null;
        $counter_a  = 0;
        $q_number   = 1;
        $flag       = false;
        // Loop data
        foreach($lines as $line){
            /**
             * Check state of current row
             */
            if(is_null($current)){
                // define
                $current = [];
            }
            // Trim line
            $line = trim($line);
            // Skip empty
            if(empty($line)){
                continue;
            }
            // Generate qid
            $qid = create_uid($category, $q_number);
            /**
             * Check for new question or continuing question
             */
            if(str_starts_with($line, "#Q")){
                /**
                 * Assign QID and Question
                 */
                $current["QID"]      = $qid;
                $current["question"] = trim(substr($line, 2));
            } elseif (str_starts_with($line, "^")){
                /**
                 * Assign Correct Answer Values
                 */
                $current["correct_text"]    = trim(substr($line, 1));
                $current["correct_OID"]     = null;
                /**
                 * Check for true / false flag
                 * - Grab trimmed answer
                 * - Check values lower than 5 characters
                 */
                $answer = trim(strtolower($current["correct_text"]));
                // Check size and boolean
                if(strlen($answer) <= 5){
                    // Check yes / no | true / false --> set flag
                    if(($answer === "yes" || $answer === "no") || ($answer === "true" || $answer === "false")){
                        $flag = true;
                    }
                }
            } elseif(preg_match('/^([A-D])\s(.+)/', $line, $matches)){
                // Set as array
                if(is_null($options)){
                    $options = [];
                }
                /**
                 * Search Answers and append to Answer Data array
                 */
                $option_key = $matches[1];
                $option_val = trim($matches[2]);
                /**
                 * Assign Values to answer data
                 */
                $options[] = [
                    "OID"   => create_uid($category . "_OPT", $option_key),
                    "QID"   => $qid,
                    "text" => $option_val
                ];
                // Increment answers counter
                $counter_a++;
            }
            /**
             * Check completed current row
             */
            if(count($current) === 4 && (($flag === true && $counter_a === 2) || $flag === false && $counter_a === 4)){
                /**
                 * Assign remaining values to question data
                 */
                $current["difficulty"]   = "easy";
                $current["category"]     = strtolower($category);
                /**
                 * Determine OID for question data
                 * - Search answers array
                 */
                foreach($options as $answer){
                    // Validate OID exists and search for string
                    if(isset($answer["OID"])){
                        if($current["correct_text"] === $answer["text"]){
                            $current["correct_OID"] = $answer["OID"];
                        }
                    }
                }
                /**
                 * Determine type of question
                 */
                if($counter_a == 2){
                    $answer = strtolower(trim($current["correct_text"]));
                    // Check correct text for string to determine true / yes
                    $current["type"] = ($answer == "yes" || $answer == "no") ? "yes_no" : "true_false";
                } else {
                    $current["type"] = "multiple_choice";
                }
                /**
                 * Push current properties to question data array
                 */
                $data_q[] = $current;
                $data_a   = array_merge($data_a, $options);
                // Reset Counters
                $counter_a = 0;
                $flag = false;
                // increment question number
                $q_number++;
                // Reset current
                $current = null;
                $options = null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Write CSV from Array of Assoc Arrays
         * @param string $fp_output Filepath output includes filename and extension
         * @param array $data array of assoc arrays 
         * 
         * @return bool failure or success
         */
        /*----------------------------------------------------------*/
        function write_csv($fp_output, $data){
            // set flag
            $flag = false;
            // CSV Properties
            $delimiter  = ',';
            $enclosure  = '"';
            $escape     = '\\';
            // Open File and check handler
            $handler = fopen($fp_output, "w");
            // Validate Handler
            if($handler !== false){
                // get headers
                $headers = array_keys($data[0]);
                // write headers to file
                if(fputcsv($handler, $headers, $delimiter, $enclosure, $escape) === false){
                    // on failure of header write
                    fclose($handler);
                } else {
                    // Loop data and write
                    $row_number = 0;
                    foreach($data as $record){
                        // get values
                        $values = array_values($record);
                        // Write to csv
                        if(fputcsv($handler, $values, $delimiter, $enclosure, $escape) === false){
                            // Set body for failure
                            $body = "Failure to write to CSV file on row: " . $row_number;
                        }
                        // increment row number
                        $row_number++;
                    }
                    // set flag
                    $flag = true;
                    // Close file
                    fclose($handler);
                }
            }
            // return result
            return $flag;
        }
        /**
         * Define Output Properties
         */
        $output_filepath    = '../src/data/raw/';
        $records_q_filename = strtolower($category) . "_" . substr(bin2hex(random_bytes(16)), 0, 4);
        $records_a_filename = $records_q_filename . "_options_";

        /**
         * Assign Body Message
         */
        $body = $flag ? "Success" : "Failed to Create Document!";
        /**
         * Send Output Response
         */
        $res->setContentType("application/json");
        $res->setStatus(200);
        //json_encode($results, JSON_PRETTY_PRINT)
        $res->setBody($body);
        $res->send();
    });
    /**
     * Dispatch all routes
     */
    $app->execute();
?>