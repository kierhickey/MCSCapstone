<?php

    /**
     * ResultState is used to give more meaning than simply returning true or false.
     */
    class ResultState implements JsonSerializable {
        private $result = false;
        private $message = "";

        /**
         * Gets the message from this ResultState.
         * @return string A string describing what resulted in the current ResultState.
         */
        public function getMessage() {
            return $this->message;
        }

        /**
         * Gets the result from this ResultState
         * @return boolean A boolean representing the result.
         */
        public function getResult() {
            return $this->result;
        }

        public function jsonSerialize() {
            return [
                "result" => $this->getResult(),
                "message" => $this->getMessage()
            ];
        }

        /**
         * Creates a new ResultState instance.
         * @param boolean $result  The result.
         * @param string  $message A message describing what resulted in the current result.
         */
        public function __construct($result, $message) {
            if (!is_bool($result) || !is_string($message)) {
                throw new Exception(sprintf("No constructor matches the signature (%s, %s); should be (boolean, string)", gettype($result), gettype($message)));
            }
            if ($result === null || $message === null) {
                throw new Exception(sprintf("NullArgumentException - you must provide a value for both parameters."));
            }

            $this->result = $result;
            $this->message = $message;
        }
    }

?>
