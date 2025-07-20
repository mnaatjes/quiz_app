<?php
    /**
     * Require PHP Manager
     */
    require_once("../../../packages/php_http_manager/src/main.php");
    // Enable Errors
    ini_errors_enable();
    /**
     * Create UID
     * @param string $category
     * @param int $i
     * @param int $length (default = 4)
     * @return string
     */
    function create_uid($category, $i, $length=4){
        return strtoupper($category) . "_" . substr(bin2hex(random_bytes(16)), 0, $length) . "_" . str_pad($i, 4, 0, STR_PAD_LEFT);
    }
    /**
     * Init $app
     */
    header("Content-Type: application/json");
    $app = new HttpManager();
    $app->get('/', function($req, $res){
        /**
         * Grab TXT file and convert to array of objects
         */
        $fp_input   = "../src/data/raw/animals.txt";
        $category   = "animal";
        // Open
        $contents = file_get_contents($fp_input);
        // Compose data
        $data_q     = [];
        $data_a     = [];
        $lines      = explode(PHP_EOL, $contents);
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
        /**
         * Create CSV File
         * - Define Properties
         * - Create File
         * - Loop Data
         * - Write out
         */
        $fp_output  = '../src/data/raw/' . strtolower($category) . "_options" . "_" . substr(bin2hex(random_bytes(16)), 0, 4) . ".csv";
        $delimiter  = ',';
        $enclosure  = '"';
        $escape     = '\\';
        /**
         * Open file
         */
        $handler = fopen($fp_output, "w");
        $body    = "Failed";
        if($handler !== false){
            // get headers
            $headers = array_keys($data_a[0]);
            // write headers to file
            if(fputcsv($handler, $headers, $delimiter, $enclosure, $escape) === false){
                // on failure of header write
                fclose($handler);
            } else {
                // Loop data and write
                $row_number = 0;
                foreach($data_a as $record){
                    // get values
                    $values = array_values($record);
                    var_dump($values);
                    // Write to csv
                    if(fputcsv($handler, $values, $delimiter, $enclosure, $escape) === false){
                        // Set body for failure
                        $body = "Failure to write to CSV file on row: " . $row_number;
                    }
                    // increment row number
                    $row_number++;
                }
                // Set body for success condition
                $body = $fp_output;
                // Close file
                fclose($handler);
            }
        }
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