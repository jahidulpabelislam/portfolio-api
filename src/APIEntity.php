<?php

namespace App;

use DateTime;

abstract class APIEntity extends Entity implements APIEntityInterface {

    public function getAPIResponse(): array {
        $response = [
            "id" => $this->getId(),
        ];

        $dateColumns = static::getDateColumns();
        $dateTimeColumns = static::getDateTimeColumns();

        foreach ($this->columns as $column => $value) {
            if ($value instanceof DateTime) {
                if (in_array($column, $dateColumns)) {
                    $value = $value->format("Y-m-d");
                }
                else if (in_array($column, $dateTimeColumns)) {
                    $value = $value->format("Y-m-d H:i:s e");
                }
            }

            $response[$column] = $value;
        }

        return $response;
    }

}
