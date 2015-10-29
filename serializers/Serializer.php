<?php

namespace leandrogehlen\exporter\serializers;

use leandrogehlen\exporter\data\Column;
use leandrogehlen\exporter\data\Dictionary;
use leandrogehlen\exporter\data\Exporter;
use leandrogehlen\exporter\data\Session;
use yii\base\Object;
use yii\helpers\ArrayHelper;


/**
 * Serializer converts DB data into specific before it is sent out
 *
 * @author Leandro Guindani Gehlen <leandrogehlen@gmail.com>
 */
abstract class Serializer extends Object
{
    /**
     * @var Exporter
     */
    public $exporter;

    /**
     * Serializes the given session into a format that can be easily turned into other formats.
     * @param Session[] $sessions
     * @param array $master
     * @return array
     */
    abstract public function serialize($sessions, $master = []);


    /**
     * Formats the specified data.
     * @param array $data
     * @return string
     */
    abstract public function formatData($data);

    /**
     * Executes the query statement and returns ALL rows at once.
     * @param string $providerName the provider name
     * @return array
     */
    protected function executeProvider($providerName, $master)
    {
        $params = [];
        $provider = $this->exporter->findProvider($providerName);

        if (preg_match_all('/:\w+/', $provider->query, $matches)) {
            foreach ($matches as $param) {
                $name = substr($param[0], 1);
                $value = ArrayHelper::getValue($master, $name);

                if ($value === null) {
                    $parameter = $this->exporter->findParameter($name);
                    if ($parameter !== null) {
                        $value = is_callable($parameter->value) ? call_user_func($parameter->value) : $value;
                    }
                }
                $params[$name] = $value;
            }
        }

        return $this->exporter->db->createCommand($provider->query, $params)->queryAll();
    }

    /**
     * Extract formatted value.
     * @param Column $column
     * @param array $row
     * @return mixed|string
     */
    protected function extractValue($column, $row)
    {
        $charComplete = $column->charComplete;
        $size = $column->size;
        $format = $column->format;
        $align = $column->align;
        $expression = null;
        $value = ArrayHelper::getValue($row, $column->name);

        if (is_callable($column->value)){
            $expression = $column->value;
        } elseif ($column->value !== null) {
            $value = $column->value;
        }

        $dictionary = $column->dictionaryName ? $this->exporter->findDictionary($column->dictionaryName) : null;
        if ($dictionary) {
            if ($dictionary->value) {
                if ($expression === null && is_callable($dictionary->value)) {
                    $expression = $dictionary->value;
                } elseif ($value === null) {
                    $value = $dictionary->value;
                }
            }
            if ($charComplete === null) {
                $charComplete = $dictionary->charComplete;
            }
            if ($align === null) {
                $align = $dictionary->align;
            }
            if ($size === null) {
                $size = $dictionary->size;
            }
            if ($format === null) {
                $format = $dictionary->format;
            }
        }

        if ($format) {
            $value = $this->exporter->formatter->format($value, $format);
        }

        if (is_callable($expression)) {
            $value = call_user_func($expression, $value);
        }

        $value = (string) $value;
        if ($size === null) {
            return $value;
        } else {
            $padding = $this->toPadding($align);
            return ($size > strlen($value)) ? str_pad($value, $size, $charComplete, $padding) : substr($value, 0, $size);
        }
    }

    /**
     * Converts the [[align]] property to valid padding type.
     * @see {@link http://php.net/manual/pt_BR/function.str-pad.php php manual}.
     * @param string $align the column alignment
     * @return int
     */
    private function toPadding($align)
    {
        if ($align == Dictionary::ALIGN_RIGHT) {
            return STR_PAD_LEFT;
        } elseif ($align == Dictionary::ALIGN_BOTH) {
            return STR_PAD_BOTH;
        } else {
            return STR_PAD_RIGHT;
        }
    }
}
