<?php

return [
    "description" => "Orders data export",
    "serializer" => "leandrogehlen\\exporter\\serializers\\ColumnSerializer",
    "charDelimiter" => "|",
    "sessions" => [
        [
            "name" => "order",
            "providerName" => "order-provider",
            "columns" => [
                ["name" => "type", "value" => "010"],
                ["name" => "number"],
                ["name" => "created_at", "value" => function ($value) {
                    return date_format(date_create($value), 'Y-m-d');
                }],
                ["name" => "person"],
                ["name" => "description"]
            ],
            "sessions" => [
                [
                    "name"  => "details",
                    "providerName" => "detail-provider",
                    "columns" => [
                        ["name" => "type", "value" => "020"],
                        ["name" => "product_id"],
                        ["name" => "quantity"],
                        ["name" => "price"],
                        ["name" => "total"]
                    ]
                ]
            ]
        ]
    ],
    "providers" => [
        [
            "name" => "order-provider",
            "query" => "select invoice.*, person.firstName as person from invoice join person on (person.id = invoice.person_id) where invoice.created_at = :created_at"
        ],[
            "name" => "detail-provider",
            "query" => "select * from invoice_details where invoice_id = :id"
        ]
    ],
    "parameters" => [
        [
            "name" => "created_at",
            "label" => "Created At",
            "value" => function() { return date('Y-m-d'); }
        ]
    ]
];