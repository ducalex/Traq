<?php
/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

namespace traq\models;

use avalon\database\Model;

/**
 * User group model.
 *
 * @package Traq
 * @subpackage Models
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class CustomField extends Model
{
    protected static $_name = 'custom_fields';
    protected static $_serialize = array('ticket_type_ids');
    protected static $_properties = array(
        'id',
        'name',
        'slug',
        'type',
        'values',
        'multiple',
        'default_value',
        'regex',
        'min_length',
        'max_length',
        'is_required',
        'project_id',
        'ticket_type_ids'
    );

    /**
     * Modified construct method to handle the `ticket_type_ids` column.
     *
     * @param array   $data
     * @param boolean $is_new
     */
    public function __construct($data = null, $is_new = true )
    {
        if ($is_new) {
            $data['ticket_type_ids'] = array();
        }
    
        parent::__construct($data, $is_new);
    }

    /**
     * Returns the custom fields for the specified project.
     *
     * @param integer $project_id
     *
     * @return array
     */
    public static function for_project($project_id)
    {
        return static::select()->where('project_id', $project_id)->exec()->fetch_all();
    }

    /**
     * Returns an array of IDs belonging to custom fields.
     *
     * @param object $project
     *
     * @return array
     */
    public static function get_ids($project = null)
    {
        $ids = array();

        // Get fields for the project if one was passed, otherwise get all.
        $fields = $project ? static::for_project($project->id) : static::fetch_all();

        foreach ($fields as $field) {
            $ids[] = $field->id;
        }

        return $ids;
    }

    /**
     * Returns an array of slugs belonging to custom fields.
     *
     * @param object $project
     *
     * @return array
     */
    public static function get_slugs($project = null)
    {
        $slugs = array();

        // Get fields for the project if one was passed, otherwise get all.
        $fields = $project ? static::for_project($project->id) : static::fetch_all();

        foreach ($fields as $field) {
            $slugs[] = $field->slug;
        }

        return $slugs;
    }

    /**
     * Returns the models properties.
     *
     * @return array
     */
    public static function properties()
    {
        return static::$_properties;
    }

    /**
     * Returns an array containing valid field types.
     *
     * @return array
     */
    public static function types()
    {
        return array(
            'text',
            'select',
            'integer'
        );
    }

    /**
     * Returns an array of valid field types formatted
     * for the Form::select() helper.
     *
     * @return array
     */
    public static function types_select_options()
    {
        $options = array();

        foreach (static::types() as $type) {
            $options[] = array('label' => l($type), 'value' => $type);
        }

        return $options;
    }

    /**
     * Returns the fields values formatted for the
     * Form::select() helper.
     *
     * @return array
     */
    public function values_select_options()
    {
        $options = array();

        foreach (explode("\n", $this->values) as $option) {
            $options[] = array('label' => $option, 'value' => $option);
        }

        return $options;
    }

    /**
     * Validates the custom field.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public function validate($value)
    {
        switch($this->type) {
            case 'text':
                return $this->validate_min_length($value) and $this->validate_max_length($value) and $this->validate_regex($value);

            case 'integer':
                return $this->validate_min_length($value) and $this->validate_max_length($value) and ctype_digit($value);

            case 'select':
                $value = (array)$value; // If single value
                return array_intersect($value, explode("\n", $this->values)) === $value;
            
            default:
                return false;
        }
    }

    /**
     * Validates the minimum length.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    private function validate_min_length($value)
    {
        return empty($this->min_length) || strlen($value) > $this->min_length;
    }

    /**
     * Validates the maximum length.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    private function validate_max_length($value)
    {
        return empty($this->max_length) || strlen($value) <= $this->max_length;
    }

    /**
     * Validates the regex.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function validate_regex($value)
    {
        return preg_match("#{$this->regex}#", $value) >= 0;
    }

    /**
     * Checks if the model data is valid.
     *
     * @return boolean
     */
    public function is_valid()
    {
        $errors = array();

        // Make sure the name is set
        if (empty($this->_data['name'])) {
            $errors['name'] = l('errors.name_blank');
        }

        // Check if the slug is empty
        if (empty($this->_data['slug'])) {
            $errors['slug'] = l('errors.slug_blank');
        }

        // Make sure the slug isn't in use
        $slug = static::select('id')->where('id', ($this->_is_new() ? 0 : $this->id), '!=')->where('slug', $this->_data['slug'])->where('project_id', $this->_data['project_id']);
        if ($slug->exec()->row_count()) {
            $errors['slug'] = l('errors.slug_in_use');
        }

        // Make sure the type is set
        if (empty($this->_data['type'])) {
            $errors['type'] = l('errors.type_blank');
        }

        // Text and integer field
        if ($this->type == 'text') {
            // Make sure regex is set
            if (empty($this->_data['regex'])) {
                $errors['regex'] = l('errors.regex_blank');
            }
        }
        // Select field
        elseif ($this->type == 'select') {
            // Make sure there are some values is set
            if (empty($this->_data['values'])) {
                $errors['values'] = l('errors.values_blank');
            }
        }

        // Set errors and return
        $this->errors = $errors;
        return empty($errors);
    }

    /**
     * Returns a string of CSS classes to be used in the custom field
     * `div` wrapper in forms to easily show or hide for relevant
     * ticket types.
     *
     * @return string
     */
    public function type_css_classes()
    {
        $classes = array();

        foreach ($this->_data['ticket_type_ids'] as $type_id) {
            $classes[] = "field-for-type-{$type_id}";
        }

        return implode(" ", $classes);
    }

    /**
     * Saves the model data.
     *
     * @return boolean
     */
    public function save()
    {
        if ($this->is_valid()) {
            // Defaults
            $defaults = array(
                'values'          => null,
                'multiple'        => 0,
                'default_value'   => null,
                'regex'           => null,
                'min_length'      => null,
                'max_length'      => null,
                'is_required'     => 0,
                'ticket_type_ids' => array()
            );

            // Merge defaults with currently set data
            $this->_data = array_merge($defaults, $this->_data);

            // Remove stupid crap
            $this->_data['values'] = str_replace("\r", '', $this->_data['values']);

            return parent::save();
        }

        return false;
    }
}
