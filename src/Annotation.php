<?php

namespace hpcarlos\model_annotation;

use Log;
use ReflectionClass;
use DB;
use Illuminate\Support\Pluralizer;

class Annotation {
    public static function annotateTable($app, $table) {
        foreach (glob("{$app->path}/*.php") + glob("{$app->path}/**/*.php") as $file) {
            $filename = basename($file);
            $name = substr($filename, 0, strlen($filename)-4);
            $singular_name = Pluralizer::singular($table);
            $possible_class_name = studly_case($singular_name);

            if ($name == $possible_class_name) {
                Log::debug('File match with table: ' . $name);

                $description = self::getTableStructure($table);
                self::updateAnnotation($file, $description);
            }
        }
    }

    protected static function getTableStructure($table) {
        $description = '';
        $fields = DB::select("select column_name, data_type, character_maximum_length, is_nullable, column_default from INFORMATION_SCHEMA.COLUMNS where table_name = '$table'");

        foreach ($fields as $field) {
            $type = $field->{'data_type'};
            if (1==preg_match('/^int/', $type)) {
                $type = 'int';
            } elseif ($type == 'timestamp') {
                $type = 'int';
            } elseif (1==preg_match('/^varying/', $type)) {
                $type = 'string';
            } else {
              // pass
            }

            if ($field->{'is_nullable'} === 'YES') {
                $type = "$type|null";
            }

            $default = $field->{'column_default'};
            if ('' == $default) {
                $default = 'null';
            }
            // $description = $description . "@property $type \${$field->{'Field'}} Type: {$field->{'Type'}}, Key: $key\n";
            $description = $description . "@field \${$field->{'column_name'}} $type \n"; # Type: {$field->{'data_type'}}\n";
        }

        return $description;
    }

    protected static function updateAnnotation($file, $description) {
        $content = file_get_contents($file);
        $exists = (0 !== preg_match("/\/\* MODEL ANNOTATION:\n/s", $content));

        /* If does not exist, we add a placeholder */
        if (!$exists) {
            $content = preg_replace('/<\?(php)?/', "<?php\n/* MODEL ANNOTATION:\nEND MODEL ANNOTATION */", $content, 1);
        }

        $content = preg_replace("/MODEL ANNOTATION:.*?END MODEL ANNOTATION/s", "MODEL ANNOTATION:\n{$description}\nEND MODEL ANNOTATION", $content, 1);
        file_put_contents($file, $content);
    }
}
