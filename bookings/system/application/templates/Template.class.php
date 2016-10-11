<?php
    /**
     * A template for HTML docs
     */
    class Template {
        private $type = "";
        private $name = "";

        private $tpl = "";

        /**
         * Creates a new template object
         * @param string $type The type (folder) of the template
         * @param string $name The name of the template
         */
        public function __construct($type, $name) {
            $this->type = $type;
            $this->name = $name;

            $file = __DIR__."/".$type."/".$name.".tpl";

            $this->tpl = file_get_contents($file);
        }

        /**
         * http://stackoverflow.com/a/1252705
         */
        private function str_replace_first($from, $to, $subject)
        {
            $from = '/'.preg_quote($from, '/').'/';

            return preg_replace($from, $to, $subject, 1);
        }

        private function parseArgument($dom, $id, $value) {
            $forEachEnd = "{{/each}}";

            $tplId = "{{".$id."}}";
            $forEachId = "{{#each $id}}";

            while (strpos($dom, $tplId) != false) {
                $dom = str_replace($tplId, $value, $dom);
            }

            while (strpos($dom, $forEachId) != false) {
                // Don't have to worry about offsets
                $forEachStart = strpos($dom, $forEachId);
                $forEachStartEnd = $forEachStart + strlen($forEachId);
                $posOfForEachEnd = strpos($dom, $forEachEnd);

                $toReplace = substr(
                    $dom,
                    $forEachStartEnd,
                    $posOfForEachEnd - $forEachStartEnd
                );

                $newDom = "";

                foreach ($value as $key => $val) {
                    $newDom .= str_replace("{{key}}", $key, $this->str_replace_first("{{display}}", $val, $toReplace));
                }

                $dom = $this->str_replace_first($forEachId, "", $dom);
                $dom = $this->str_replace_first($forEachEnd, "", $dom);
                $dom = $this->str_replace_first($toReplace, $newDom, $dom);
            }

            return $dom;
        }

        /**
         * Parses all of the argument passed, placing them in the HTML DOM and
         * returning them
         * @param  Array $args An array of key-value pairs to parse
         */
        public function toHtml($args) {
            $dom = $this->tpl;

            foreach ($args as $key => $val) {
                $dom = $this->parseArgument($dom, $key, $val);
            }

            return $dom;
        }
    }
?>
