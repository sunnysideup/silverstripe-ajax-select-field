<?php

namespace Sunnysideup\AjaxSelectField;

use SilverStripe\Forms\FormField;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

/**
 * Ajax multi select field.
 *
 * Allows to select multiple values/items using a custom api endpoint or callback function.
 *
 * Usage:
 * ```
 * AjaxMultiSelectField::create('MyField', 'AjaxSelectExample')
 *      ->setSearchCallback(
 *          function ($query, $request) {
 *              // Return detail info for the selected ids on load
 *              if ($ids = $request->getVar('ids')) {
 *                  foreach (SiteTree::get()->filter('ID', $ids) as $page) {
 *                      return [
 *                          'id' => $page->ID,
 *                          'title' => $page->Title,
 *                          'urlSegment' => $page->URLSegment // example of a custom field, see also below
 *                      ];
 *                  }
 *              }
 *
 *              $results = [];
 *              foreach (SiteTree::get()->filter('Title:PartialMatch', $query) as $page) {
 *                  $results[] = [ 'id' => $page->ID, 'title' => $page->Title, 'urlSegment' => $page->URLSegment ];
 *              }
 *
 *              return $results;
 *          }
 *      )->setDisplayFields([ 'title' => 'Custom Label', 'urlSegment' => 'URL' ])
 * ```
 */
class AjaxMultiSelectField extends FormField
{
    use AjaxSelectFieldTrait;

    private $displayFields = [];

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
    }

    public function Field($properties = [])
    {
        if (! $this->searchEndpoint && ! $this->searchCallback) {
            throw new \Exception(_t(__CLASS__ . '.ERROR_SEARCH_CONFIG'));
        }

        Requirements::javascript('sunnysideup/silverstripe-ajax-select-field: client/dist/ajaxMultiSelectField.js');
        Requirements::css('sunnysideup/silverstripe-ajax-select-field: client/dist/ajaxMultiSelectField.css');

        return parent::Field($properties);
    }

    /**
     * Set the fields which should be shown for selected items.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setDisplayFields($fields): AjaxMultiSelectField
    {
        $this->displayFields = $fields;

        return $this;
    }

    public function getDisplayFields(): array
    {
        if (! $this->displayFields) {
            return [
                'id' => 'ID',
                'title' => 'Title',
            ];
        }

        return $this->displayFields;
    }

    /**
     * Get the payload/config passed to the vue component.
     */
    public function getPayload(): string
    {
        return json_encode(
            [
                'id' => $this->ID(),
                'name' => $this->getName(),
                'value' => $this->getValueForComponent(),
                'lang' => substr(Security::getCurrentUser()->Locale, 0, 2),
                'config' => [
                    'minSearchChars' => $this->minSearchChars,
                    'searchEndpoint' => $this->searchEndpoint ?: $this->Link('search'),
                    'placeholder' => $this->placeholder ?: _t(__CLASS__ . '.SEARCH_PLACEHOLDER'),
                    'getVars' => $this->getVars,
                    'headers' => $this->searchHeaders,
                    'displayFields' => $this->getDisplayFields(),
                ],
            ]
        );
    }

    /**
     * Get the current value prepared for the vue component.
     */
    private function getValueForComponent(): ?array
    {
        if ($value = $this->Value()) {
            return json_decode($value, true);
        }

        return null;
    }
}
