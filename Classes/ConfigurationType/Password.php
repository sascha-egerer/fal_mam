<?php
namespace Crossmedia\FalMam\ConfigurationType;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper;

/**
 *  This class is used to display a password input field in the extension configuration
 */
class Password {

    /**
     * Tag builder instance
     *
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $tag = NULL;

    /**
     * constructor of this class
     */
    public function __construct() {
        $this->tag = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder');
    }

    /**
     * render textarea for extConf
     *
     * @param array $parameter
     * @param TypoScriptConstantsViewHelper $parentObject
     * @return string
     */
    public function render(array $parameter = array(), TypoScriptConstantsViewHelper $parentObject) {
        $this->tag->setTagName('input');
        $this->tag->forceClosingTag(TRUE);
        $this->tag->addAttribute('type', 'password');
        $this->tag->addAttribute('style', 'width:300px');
        $this->tag->addAttribute('name', $parameter['fieldName']);
        $this->tag->addAttribute('id', 'em-' . $parameter['fieldName']);
        if ($parameter['fieldValue'] !== NULL) {
            $this->tag->addAttribute('value', trim($parameter['fieldValue']));
        }
        return $this->tag->render() . '<span class="info">(password)</span>';
    }

}