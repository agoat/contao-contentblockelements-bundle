<?php
 
 /**
 * Contao Open Source CMS - ContentBlocks extension
 *
 * Copyright (c) 2016 Arne Stappen (aGoat)
 *
 *
 * @package   contentblocks
 * @author    Arne Stappen <http://agoat.de>
 * @license	  LGPL-3.0+
 */


namespace Agoat\ContentElements;

use Contao\ContentElement;
use Contao\System;
use Contao\TemplateLoader;
use Agoat\ContentElements\Template;
use Agoat\ContentElements\Pattern;


class ContentBlockElement extends ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'cb_standard';
	
	
	protected $objElement;

	protected $objBlock;
	

	
	/**
	 * Initialize the object
	 *
	 * @param \ContentModel $objModel
	 * @param string        $strColumn
	 */
	public function __construct($objModel, $strColumn='main')
	{
		if ($objModel instanceof \Model)
		{
			$this->objModel = $objModel;
		}
		elseif ($objModel instanceof \Model\Collection)
		{
			$this->objModel = $objModel->current();
		}
		
		$this->arrData = $objModel->row();
		$this->strColumn = $strColumn;
	}	
	

	/**
	 * Prepare the pattern data for the template
	 *
	 * @return string
	 */
	public function generate()
	{		
		// get the content element model object
		$this->objElement = \ElementsModel::findOneByAlias($this->type);

		if ($this->objElement === null)
		{
			return;
		}

		// Don´t show invisible content elements
		if ($this->objElement->invisible)
		{
			System::log('Content element "'.$this->type.'" with parent record "' . $this->ptable . '.id=' . $this->pid . '" is invisible and will not be shown', __METHOD__, TL_ERROR);
			return;
		}
		
		// Register the custom template
		if (!array_key_exists($this->objElement->template, TemplateLoader::getFiles()))
		{
			TemplateLoader::addFile($this->objElement->template, $this->objElement->getRelated('pid')->templates);
		}

		$this->strTemplate = $this->objElement->template;
				
		if (TL_MODE == 'FE' && !BE_USER_LOGGED_IN && ($this->invisible || ($this->start != '' && $this->start > time()) || ($this->stop != '' && $this->stop < time())))
		{
			return '';
		}

		$this->Template = new Template($this->strTemplate);
	
		// Deliver some general element data
		$this->Template->setData(
			array (
				'id' => $this->id,
				'pid' => $this->pid,
				'ptable' => $this->ptable,
				'tstamp' => $this->tstamp,
				'start' => $this->start,
				'stop' => $this->stop,
				'protected' => $this->protected,
				'inColumn' => $this->strColumn
			)
		);

		// compile pattern to prepare values
		$this->compile();
		
		return $this->Template->parse();
	}

	
	/**
	 * Generate content element
	 */
	protected function compile()
	{		
		// get the pattern model collection
		$colPattern = \PatternModel::findPublishedByPidAndTable($this->objElement->id, 'tl_elements');

		if ($colPattern === null)
		{
			return;
		}
		
		// get correct element id (included content element) see #37
		$intCid = ($this->origId) ? $this->origId : $this->id;
		
		// get data for content elements
		$colData = \DataModel::findByCid($intCid);

		if ($colData !== null)
		{
			foreach ($colData as $objData)
			{
				$arrData[$objData->pid][$objData->rid] = $objData;
			}							
		}
		
		// prepare values for every pattern
		foreach($colPattern as $objPattern)
		{
			// don´t show the invisible or system pattern
			if (in_array($objPattern->type, $GLOBALS['TL_CTP_SYS']))
			{
				continue;
			}
			
			$strClass = Pattern::findClass($objPattern->type);
				
			if (!class_exists($strClass))
			{
				System::log('Pattern element class "'.$strClass.'" (pattern element "'.$objPattern->type.'") does not exist', __METHOD__, TL_ERROR);
			}
			else
			{
				$objPatternClass = new $strClass($objPattern);
				$objPatternClass->cid = $intCid;
				$objPatternClass->rid = 0;
				$objPatternClass->Template = $this->Template;
				$objPatternClass->arrData = $arrData;
				$objPatternClass->Value = $arrData[$objPattern->id][0];
				
				$objPatternClass->compile();
			}
		}
	}
}
