<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'parent_id', 'input_label', 'price','is_select_multiple_child','is_add_on','is_state_select',
    'is_need_appointment','is_private'];

       /**
     * The "booted" method of the model.
     */
       /**
     * Update the parent service based on request data.
     */
    public function updateParentFromRequest(array $requestData)
    {
        if ($this->parent_id) {
            $parent = $this->parent;
            if ($parent) {
                // Update the parent's input_label if it exists in the request data
                if (isset($requestData['input_label'])) {
                    $parent->addInputLabel($requestData['input_label']);
                }

                // Update the parent's is_select_multiple_child if it exists in the request data
                if (isset($requestData['is_select_multiple_child'])) {
                    $parent->update(['is_select_multiple_child' => $requestData['is_select_multiple_child']]);
                    $this->update(['is_select_multiple_child' => false]); // Reset the child's value
                }

                // Update the parent's is_add_on if it exists in the request data
                if (isset($requestData['is_add_on'])) {
                    $parent->update(['is_add_on' => $requestData['is_add_on']]);
                    $this->update(['is_add_on' => false]); // Reset the child's value
                }
            }
        }
    }
    /**
     * Get the parent service.
     */
    public function parent()
    {
        return $this->belongsTo(Service::class, 'parent_id')
            ->where('is_private', false); // Only return non-private parents
    }

    /**
     * Get the child services.
     */
    public function children()
    {
        return $this->hasMany(Service::class, 'parent_id')
            ->where('is_private', false) // Only return non-private children
            ->with('children'); // Recursive relationship
    }
    /**
     * Get all descendants of a service (including self).
     */
    public function descendantsAndSelf()
    {
        // Start with the current service (self) and include all children recursively
        $descendants = collect([$this]);

        foreach ($this->children as $child) {
            $descendants = $descendants->merge($child->descendantsAndSelf());
        }

        return $descendants;
    }

    /**
     * Check if the service is the last level child.
     */
    public function isLastLevel()
    {
        return $this->children->isEmpty(); // No children means it's the last level
    }

    /**
     * Add dynamic input label to the service.
     */
    public function addInputLabel($label)
    {
        $this->input_label = $label;
        $this->save();
    }

    /**
     * Set the price for the last level child.
     */
    public function setPrice($price)
    {
        if ($this->isLastLevel()) {
            $this->price = $price;
            $this->save();
        }
    }

    /**
     * Accessor for is_last_level.
     */
    public function getIsLastLevelAttribute()
    {
        return $this->isLastLevel();
    }

    public function getPriceAttribute($value)
    {
        return (float) number_format($value, 2, '.', ''); // Format to two decimal places
    }
    /**
     * Append is_last_level to the model's array/JSON representation.
     */
    protected $appends = ['is_last_level'];


     /**
     * Accessor for is_select_multiple_child.
     */
    public function getIsSelectMultipleChildAttribute($value)
    {
        return (bool) $value; // Convert 0/1 to false/true
    }

    /**
     * Accessor for is_add_on.
     */
    public function getIsAddOnAttribute($value)
    {
        return (bool) $value; // Convert 0/1 to false/true
    }

    /**
     * Accessor for is_state_select.
     */
    public function getIsStateSelectAttribute($value)
    {
        return (bool) $value; // Convert 0/1 to false/true
    }

    public function getIsNeedAppointmentAttribute($value)
    {
        return (bool) $value; // Convert 0/1 to false/true
    }


}
