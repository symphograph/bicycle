<?php

namespace Symphograph\Bicycle\Contact;

use Symphograph\Bicycle\Auth\Account\Profile\AccProfileITF;

class ContactPowered extends Contact
{
    /**
     * @var int[]
     */
    public readonly array $powerIds;

    public static function NewPoweredInstance(string $strValue, ContactType $contactType, array $powerIds): self
    {
        $contact = new self();
        $contact->strValue = $strValue;
        $contact->type = $contactType->value;
        $contact->powerIds = $powerIds;
        return $contact;
    }

    public function isEqual(AccProfileITF $profile): bool
    {
        $contact = $profile->getContact();

        $str1 = mb_strtolower($this->strValue);
        $str2 = mb_strtolower($contact->strValue);
        if($str1 !== $str2) return false;

        return $contact->type === $this->type;
    }
}