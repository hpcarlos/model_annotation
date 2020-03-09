<?php

namespace hpcarlos\model_annotation;

use Log;
use ReflectionClass;
use DB;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;

class Annotation {
    public static function annotateTable($app, $table) {
        foreach (glob("{$app->path}/*.php") + glob("{$app->path}/**/*.php") as $file) {
            $filename = basename($file);
            $name = substr($filename, 0, strlen($filename)-4);
            $singular_name = Pluralizer::singular($table);
            $possible_class_name = Str::studly($singular_name);

            if ($name == $possible_class_name) {
                Log::debug('File match with table: ' . $name);

                $description = self::getTableStructure($table);
                self::removeOldAnnotation($file);
                self::updateAnnotation($file, $description, $table);
            }
        }
    }

    protected static function getTableStructure($table) {
        $description = '';
        $fields = DB::select("select column_name, data_type, character_maximum_length, is_nullable, column_default from INFORMATION_SCHEMA.COLUMNS where table_name = '$table'");
        $max_size_field = 0;
        $ext = '!---------------------------------------------------------------------------------------------------------------------------------------------------------------';

        foreach ($fields as $field) {
          $max_size_field = (strlen($field->{'column_name'}) >  $max_size_field) ? strlen($field->{'column_name'}) : $max_size_field ;
        }

        foreach ($fields as $field) {
            $type = $field->{'data_type'};
            if (1==preg_match('/^int/', $type)) {
                $type = 'int';
            } elseif ($type == 'timestamp') {
                $type = 'int';
            } elseif (1==preg_match('/varying/', $type)) {
                $type = 'string';
            } elseif (1==preg_match('/timestamp/', $type)) {
              $type = 'timestamp';
            } else {
              // pass
            }

            if ($field->{'is_nullable'} === 'NO') {
                $type = "$type, not null";
            }

            $default = $field->{'column_default'};
            if ('' == $default) {
                $default = 'null';
            }

            $remove = strlen($ext) - ($max_size_field - strlen($field->{'column_name'}));
            $new_ext = substr($ext, 0, -$remove);

            $new_ext = str_replace('!', ' ', $new_ext);
            $new_ext = str_replace('-', ' ', $new_ext);

            $description = $description . "# \${$field->{'column_name'}} $new_ext :$type \n"; # Type: {$field->{'data_type'}}\n";
        }

        return $description;
    }

    protected static function updateAnnotation($file, $description, $table_name) {
      $content = file_get_contents($file);
      $exists = (0 !== preg_match_all("/\# == Schema Information\n/s", $content));

      /* If does not exist, we add a placeholder */
      if (!$exists) {
        $content = preg_replace('/<\?(php)?/', "<?php\n# == Schema Information\n# == End schema Information", $content, 1);
      }

      $content = preg_replace("/== Schema Information.*?# == End schema Information/s", "== Schema Information\n#\n# == Table name: $table_name\n#\n{$description}#\n# == End schema Information", $content, 1);
      file_put_contents($file, $content);
    }

    protected static function removeOldAnnotation($file) {
      $content = file_get_contents($file);
      $content = preg_replace("/MODEL ANNOTATION:.*?END MODEL ANNOTATION/s", "", $content, 1);
      $content = str_replace("/*  */\n", "", $content);
      file_put_contents($file, $content);
    }

}
