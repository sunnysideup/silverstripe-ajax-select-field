<?php

namespace Sunnysideup\AjaxSelectField;

use SilverStripe\Forms\FormField;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

/**
 * Ajax Select / Dropdown field.
 *
 * Allows to select a single value/entry using a custom api endpoint or callback function.
 *
 * Usage:
 * ```
 * AjaxSelectField::create('MyField', 'AjaxSelectExample')
 *      ->setSearchCallback(
 *          function ($query, $request) {
 *              // This part is only required if the idOnlyMode is active
 *              if ($id = $request->getVar('id')) {
 *                  $page = SiteTree::get()->byID($id);
 *
 *                  return [
 *                      'id' => $page->ID,
 *                      'title' => $page->Title
 *                  ];
 *              }
 *
 *              $results = [];
 *              foreach (SiteTree::get()->filter('Title:PartialMatch', $query) as $page) {
 *                  $results[] = [ 'id' => $page->ID, 'title' => $page->Title ];
 *              }
 *
 *              return $results;
 *          }
 *      )
 * ```
 */
class AjaxSelectField extends FormField
{
    use AjaxSelectFieldTrait;

    /**
     * @var bool Option to store only the id instead of the full selection payload
     */
    private $idOnlyMode = false;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
    }

    public function Field($properties = [])
    {
        if (! $this->searchEndpoint && ! $this->searchCallback) {
            throw new \Exception(_t(__CLASS__ . '.ERROR_SEARCH_CONFIG'));
        }

        Requirements::javascript('sunnysideup/silverstripe-ajax-select-field: client/dist/ajaxSelectField.js');
        Requirements::css('sunnysideup/silverstripe-ajax-select-field: client/dist/ajaxSelectField.css');

        return parent::Field($properties);
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
                    'idOnlyMode' => $this->idOnlyMode,
                ],
            ]
        );
    }

    /**
     * En-/disable the idOnlyMode.
     *
     * If active the field will only store the "id" of the selected result.
     * Otherwise the full result payload will be stored.
     *
     * Note that the search endpoint or callback has to support requests with a ?id param
     * returning only that one result if the mode is active.
     *
     * @param bool $idOnlyModeActive
     *
     * @return $this
     */
    public function setIdOnlyMode($idOnlyModeActive): AjaxSelectField
    {
        $this->idOnlyMode = $idOnlyModeActive;

        return $this;
    }

    /**
     * Get the current value prepared for the vue component (depending on the mode).
     *
     * @return null|array|int|string
     */
    private function getValueForComponent()
    {
        if ($value = $this->Value()) {
            if ($this->idOnlyMode) {
                return $value;
            }

            return json_decode($value, true);
        }

        return null;
    }
}
