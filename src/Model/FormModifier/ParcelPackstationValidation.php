<?php

declare(strict_types=1);

namespace Hyva\HyvaShippingDhl\Model\FormModifier;

use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;

class ParcelPackstationValidation implements EntityFormModifierInterface
{
    /**
     * Apply custom validation logic to the form.
     *
     * @param EntityFormInterface $form
     * @return EntityFormInterface
     */
    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        // Register a modification listener for your form field validation
        $form->registerModificationListener(
            'validate-parcel-packstation-field',
            'form:build',
            [$this, 'validateParcelPackstationField']
        );

        return $form;
    }

    /**
     * Validate the parcel packstation field.
     *
     * @param EntityFormInterface $form
     */
    public function validateParcelPackstationField(EntityFormInterface $form)
    {
        // Get the value of the parcel packstation field from the form
        $parcelPackstationValue = $form->getField('your_field_name')->getValue();

        // Implement your custom validation logic here
        if ($this->isValidParcelPackstation($parcelPackstationValue)) {
            // Field is valid, do nothing
        } else {
            // Field is invalid, set an error message
            $form->getField('your_field_name')->setError('Invalid parcel packstation value');
        }
    }

    /**
     * Implement your custom validation logic for the parcel packstation field.
     *
     * @param string $value
     * @return bool
     */
    private function isValidParcelPackstation(string $value): bool
    {
        // Implement your validation logic here
        // Return true if the value is valid, false otherwise
    }
}
