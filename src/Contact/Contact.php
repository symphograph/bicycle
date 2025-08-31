<?php

namespace Symphograph\Bicycle\Contact;

class Contact
{
    public string $strValue;
    public string $type;

    public static function NewInstance(string $strValue, ContactType $contactType): static
    {
        $contact = new self();
        $contact->strValue = $strValue;
        $contact->type = $contactType->value;
        return $contact;
    }
}