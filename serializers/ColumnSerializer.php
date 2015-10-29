<?php

namespace leandrogehlen\exporter\serializers;

/**
 * Formats the given data into an column size or separator char response content.
 *
 * @author Leandro Guindani Gehlen <leandrogehlen@gmail.com>
 */
class ColumnSerializer extends Serializer
{
    /**
     * @inheritdoc
     */
    public function serialize($sessions, $master = [])
    {
        $data = [];
        foreach ($sessions as $session) {
            if ($session->exported) {
                $i = 0;
                $rows = $this->executeProvider($session->providerName, $master);

                foreach ($rows as $row) {

                    $record = [];
                    foreach ($session->columns as $column) {
                        $value = $this->extractValue($column, $row);
                        $record[] = $value;
                    }

                    $data[] = implode($this->exporter->charDelimiter, $record);
                    $children = $this->serialize($session->sessions, $row);

                    foreach ($children as $item) {
                        $data[] = $item;
                    }
                    $session->rows = $i++;
                }
            }
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function formatData($data)
    {
        return implode("\n", $data);
    }
}