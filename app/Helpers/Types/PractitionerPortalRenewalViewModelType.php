<?php
namespace App\Helpers\Types;

class PractitionerPortalRenewalViewModelType
{
    public string $action;
    public $data;
    public string $message;
    /**
     * fields to be field and submitted for this action
     * @var FormFieldType[]
     */
    public array $formFields;

    public ?bool $withdrawable;

    public ?string $renewalUuid;

    public function __construct(string $action, $data, string $message, array $formFields, ?bool $withdrawable = null, ?string $renewalUuid = null)
    {
        $this->action = $action;
        $this->data = $data;
        $this->formFields = $formFields;
        $this->message = $message;
        $this->withdrawable = $withdrawable;
        $this->renewalUuid = $renewalUuid;
    }

}