<?php
// $HeadURL: https://joomgallery.org/svn/joomgallery/JG-2.0/Plugins/JoomDisplayCbname/trunk/joomdisplaycbname.php $
// $Id: joomdisplaycbname.php 3297 2011-08-24 22:24:21Z chraneco $
/****************************************************************************************\
**   Plugin 'JoomDisplayCBName' 3.0                                                     **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2010 - 2014 JoomGallery::ProjectTeam                                 **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

jimport('joomla.plugin.plugin');

/**
 * JoomGallery Display Community Builder Name Plugin
 *
 * @package     Joomla
 * @subpackage  JoomGallery
 * @since       1.5
 */
class plgJoomGalleryJoomDisplayCBName extends JPlugin
{
  /**
   * Constructor
   *
   * @param   object  $subject  The object to observe
   * @param   object  $params   The object that holds the plugin parameters
   * @return  plgJoomGalleryJoomDisplayCBName
   * @since   1.5
   */
  public function __construct(&$subject, $params)
  {
    parent::__construct($subject, $params);

    // Load the language file
    $this->loadLanguage();

    $file = JPATH_ADMINISTRATOR.'/components/com_comprofiler/plugin.foundation.php';
    if(file_exists($file))
    {
      global $ueConfig;
      require_once JPATH_ADMINISTRATOR.'/components/com_comprofiler/ue_config.php';
      require_once $file;
      cbimport('cb.database');
      JHtml::_('behavior.framework');
      JHtml::_('behavior.tooltip');
    }
    else
    {
      JFactory::getApplication()->enqueueMessage(JText::_('PLG_JOOMGALLERY_JOOMDISPLAYCBNAME_CB_SEEMS_NOT_TO_BE_INSTALLED'), 'warning');

      $this->_subject->detach($this);
    }
  }

  /**
   * OnJoomDisplayUser method
   *
   * Method links a user name with the corresponding Community Builder profile.
   *
   * @param   int     $userId   The ID of the user to display
   * @param   boolean $realName True, if the user's full name shall be displayed
   * @param   string  $context  The context in which the name will be displayed
   * @return  string  The HTML code created for displaying the user's name
   * @since   1.5
   */
  public function onJoomDisplayUser($userId, $realName, $context = null)
  {
    $userId = intval($userId);

    $cbUser = CBuser::getInstance($userId);

    if(!$cbUser)
    {
      return false;
    }

    $cbUserData = $cbUser->getUserData();

    $name       = $realName ? $cbUserData->name : $cbUserData->username;

    if(!$name)
    {
      return false;
    }

    $link = 'index.php?option=com_comprofiler&task=userProfile&user='.$userId.$this->_getItemid();

    // Directly link to gallery tab, if present
    if(file_exists(JPATH_ROOT.'/components/com_comprofiler/plugin/user/plug_gallery-tab/cb.gallerytab.php'))
    {
      $link .= '&tab=getgallerytab';
    }

    // Create tooltip with avatar
    $overlib = '<img src="'.$cbUser->avatarFilePath().'" alt="'.JText::sprintf('PLG_JOOMGALLERY_JOOMDISPLAYCBNAME_AVATAR_OF', $name).'" />';

    if($context == 'comment')
    {
      $html = '<a href="'.JRoute::_($link).'">'.$name.'</a><br /><a href="'.JRoute::_($link).'">'.$overlib.'</a>';

      return $html;
    }

    $overlib  = htmlspecialchars($overlib, ENT_QUOTES, 'UTF-8');

    $html = '<span class="hasTip" title="'.$name.'::'.$overlib.'"><a href="'.JRoute::_($link).'">'.$name.'</a></span>';

    return $html;
  }

  /**
   * Returns an Itemid which is associated with Community Builder
   *
   * @return  string  A string for URLs with the Itemid ('&Itemid=X')
   * @since   1.5
   */
  protected function _getItemid()
  {
    if($Itemid = $this->params->get('valid_Itemid_string', ''))
    {
      return $Itemid;
    }

    $Itemid = intval($this->params->get('Itemid', 0));

    if($Itemid)
    {
      $Itemid = '&Itemid='.$Itemid;
      $this->params->set('valid_Itemid_string', $Itemid);

      return $Itemid;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true)
          ->select('id')
          ->from($db->qn('#__menu'))
          ->where('link LIKE '.$db->q('%com_comprofiler%'))
          ->where('access = '.(int) JFactory::getApplication()->getCfg('access'))
          ->order('id DESC');
    $db->setQuery($query);

    if($Itemid = $db->loadResult())
    {
      $Itemid = '&Itemid='.$Itemid;
    }
    else
    {
      $query->clear('where')
            ->where('link LIKE '.$db->q('%com_comprofiler%'))
            ->where('access != '.(int) JFactory::getApplication()->getCfg('access'));
      $db->setQuery($query);
      if($Itemid = $db->loadResult())
      {
        $Itemid = '&Itemid='.$Itemid;
      }
      else
      {
        $Itemid = '';
      }
    }

    $this->params->set('valid_Itemid_string', $Itemid);

    return $Itemid;
  }
}