<?php

/*
 * Custom content elements extension for Contao Open Source CMS.
 *
 * @copyright  Arne Stappen (alias aGoat) 2017
 * @package    contao-customcontentelements
 * @author     Arne Stappen <mehh@agoat.xyz>
 * @link       https://agoat.xyz
 * @license    LGPL-3.0
 */

namespace Agoat\CustomContentElementsBundle\Contao;



/**
 * Provide methods to handle the input field "visualselect"
 */
class VisualSelectMenu extends \Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'mandatory':
				if ($varValue)
				{
					$this->arrAttributes['required'] = 'required';
				}
				else
				{
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'size':
				if ($this->multiple)
				{
					$this->arrAttributes['size'] = $varValue;
				}
				break;

			case 'multiple':
				if ($varValue)
				{
					$this->arrAttributes['multiple'] = 'multiple';
				}
				break;

			case 'options':
				$this->arrOptions = \StringUtil::deserialize($varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Check for a valid option (see #4383)
	 */
	public function validate()
	{
		$varValue = $this->getPost($this->strName);

		if (!empty($varValue) && !$this->isValidOption($varValue))
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalid'], (is_array($varValue) ? implode(', ', $varValue) : $varValue)));
		}

		parent::validate();
	}


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrOptions = array();
		$strClass = 'tl_select';
		$strStyle = '<style>.group-result {padding-top: 5px !important; line-height: 20px !important;}';
		$count = 1;

		if ($this->multiple)
		{
			$this->strName .= '[]';
			$strClass = 'tl_mselect';
		}

		// Add an empty option (XHTML) if there are none
		if (empty($this->arrOptions))
		{
			$this->arrOptions = array(array('value'=>'', 'label'=>'-'));
		}
		
		foreach ($this->arrOptions as $strKey=>$arrOption)
		{
			if (isset($arrOption['value']))
			{
				$arrOptions[] = sprintf('<option value="%s"%s>%s</option>',
										 \StringUtil::specialchars($arrOption['value']),
										 $this->isSelected($arrOption),
										 $arrOption['label']);

				if (isset($GLOBALS['TL_CTB_IMG'][$arrOptgroup['value']]))
				{
					$objImage = \FilesModel::findByUuid($GLOBALS['TL_CTB_IMG'][$arrOptgroup['value']]);
				}
				
				if ($objImage === null)
				{
					$strStyle .= '#ctrl_type_chzn_o_'.$count.' {position: relative; padding-left: 90px; line-height: 41px;}#ctrl_type_chzn_o_'.$count++.'::before {content: ""; position: absolute; left: 20px; top:2px; width: 60px; height: 40px; border: 1px solid #ccc; background: #eee; background-size: 60px 40px;}';
				}
				else
				{
					$strStyle .= '#ctrl_type_chzn_o_'.$count.' {position: relative; padding-left: 90px; line-height: 41px;}#ctrl_type_chzn_o_'.$count++.'::before {content: ""; position: absolute; left: 20px; top:2px; width: 60px; height: 40px; border: 1px solid #ccc; background: #eee url('.$objImage->path.'); background-size: 60px 40px;}';
				}
			}
			else
			{
				$arrOptgroups = array();

				foreach ($arrOption as $arrOptgroup)
				{
					$arrOptgroups[] = sprintf('<option value="%s"%s>%s</option>',
											   \StringUtil::specialchars($arrOptgroup['value']),
											   $this->isSelected($arrOptgroup),
											   $arrOptgroup['label']);

					if (isset($GLOBALS['TL_CTB_IMG'][$arrOptgroup['value']]))
					{
						$objImage = \FilesModel::findByUuid($GLOBALS['TL_CTB_IMG'][$arrOptgroup['value']]);
					}

					if ($objImage === null)
					{
						$strStyle .= '#ctrl_type_chzn_o_'.$count.' {position: relative; padding-left: 90px; line-height: 41px;}#ctrl_type_chzn_o_'.$count++.'::before {content: ""; position: absolute; left: 20px; top:2px; width: 60px; height: 40px; border: 1px solid #ccc; background: #eee; background-size: 60px 40px;}';
					}
					else
					{
						$strStyle .= '#ctrl_type_chzn_o_'.$count.' {position: relative; padding-left: 90px; line-height: 41px;}#ctrl_type_chzn_o_'.$count++.'::before {content: ""; position: absolute; left: 20px; top:2px; width: 60px; height: 40px; border: 1px solid #ccc; background: #eee url('.$objImage->path.'); background-size: 60px 40px;}';
					}

				}

				$arrOptions[] = sprintf('<optgroup label="&nbsp;%s">%s</optgroup>', \StringUtil::specialchars($strKey), implode('', $arrOptgroups));
				$count++;
			}
		}

		// Chosen
		if ($this->chosen)
		{
			$strClass .= ' tl_chosen';
		}


		$strStyle .= '</style>';
	
		return sprintf('%s<select name="%s" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s',
						($this->multiple ? '<input type="hidden" name="'. rtrim($this->strName, '[]') .'" value="">' : ''),
						$this->strName,
						$this->strId,
						$strClass,
						(($this->strClass != '') ? ' ' . $this->strClass : ''),
						$this->getAttributes(),
						implode('', $arrOptions),
						$strStyle);
	}
}
