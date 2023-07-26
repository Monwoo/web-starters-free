<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Serializer;

use App\Entity\BillingConfig;

// https://stackoverflow.com/questions/54645363/use-the-circular-reference-handler-key-of-the-context-instead-symfony-4-2
class CircularReferenceHandler
{
    public function __invoke($object)
    {
        if ($object instanceof BillingConfig) {
            return [
                "clientSlug" => $object->getClientSlug()
            ]; // "****";    
        }
        // return $object->getId(); // "****";
        return [
            "id" => $object->getId()
        ]; // "****";
    }
}