<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Entity;

/**
 * PageLayout
 */
class PageLayout extends \Eccube\Entity\AbstractEntity
{
    // 配置ID
    /** 配置ID: 未使用 */
    const TARGET_ID_UNUSED = 0;
    /** 配置ID: LeftNavi */
    const TARGET_ID_LEFT = 1;
    /** 配置ID: MainHead */
    const TARGET_ID_MAIN_HEAD = 2;
    /** 配置ID: RightNavi */
    const TARGET_ID_RIGHT = 3;
    /** 配置ID: MainFoot */
    const TARGET_ID_MAIN_FOOT = 4;
    /** 配置ID: TopNavi */
    const TARGET_ID_TOP = 5;
    /** 配置ID: BottomNavi */
    const TARGET_ID_BOTTOM = 6;
    /** 配置ID: HeadNavi */
    const TARGET_ID_HEAD = 7;
    /** 配置ID: HeadTopNavi */
    const TARGET_ID_HEAD_TOP = 8;
    /** 配置ID: FooterBottomNavi */
    const TARGET_ID_FOOTER_BOTTOM = 9;
    /** 配置ID: HeaderInternalNavi */
    const TARGET_ID_HEADER_INTERNAL = 10;

    /**
     * Get ColumnNum
     *
     * @return integer
     */
    public function getColumnNum()
    {
        return 1 + ($this->getLeftNavi() ? 1 : 0) + ($this->getRightNavi() ? 1 : 0);
    }

    public function getTheme()
    {
        $hasLeft = $this->getLeftNavi() ? true : false;
        $hasRight = $this->getRightNavi() ? true : false;

        $theme = 'theme_main_only';
        if ($hasLeft && $hasRight) {
            $theme = 'theme_side_both';
        } elseif ($hasLeft) {
            $theme = 'theme_side_left';
        } elseif ($hasRight) {
            $theme = 'theme_side_right';
        }

        return $theme;
    }

    /**
     * Get LeftNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getLeftNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_LEFT);
    }

    /**
     * Get MainHead
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getMainHead()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_MAIN_HEAD);
    }

    /**
     * Get RightNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getRightNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_RIGHT);
    }

    /**
     * Get MainFoot
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getMainFoot()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_MAIN_FOOT);
    }

    /**
     * Get TopNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getTopNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_TOP);
    }

    /**
     * Get BottomNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getBottomNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_BOTTOM);
    }

    /**
     * Get 
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getHeadNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_HEAD);
    }

    /**
     * Get HeaderTopNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getHeaderTopNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_HEAD_TOP);
    }

    /**
     * Get FooterBottomNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getFooterBottomNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_FOOTER_BOTTOM);
    }

    /**
     * Get HeaderInternalNavi
     *
     * @return \Eccube\Entity\Bloc[]
     */
    public function getHeaderInternalNavi()
    {
        return $this->getBlocsByTargetId(self::TARGET_ID_HEADER_INTERNAL);
    }

    /**
     * Get BlocsByTargetId
     *
     * @param integer $target_id
     * @return \Eccube\Entity\Bloc[]
     */
    public function getBlocsByTargetId($target_id)
    {
        $Blocs = array();
        foreach ($this->getBlocPositions() as $BlocPositions) {
            if ($BlocPositions->getTargetId() === $target_id) {
                $Blocs[] = $BlocPositions->getBloc();
            }
        }
        return $Blocs;
    }


    /**
     * @var integer
     */
    private $device_type_id;

    /**
     * @var integer
     */
    private $page_id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var integer
     */
    private $header_chk;

    /**
     * @var integer
     */
    private $footer_chk;

    /**
     * @var integer
     */
    private $edit_flg;

    /**
     * @var string
     */
    private $author;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $keyword;

    /**
     * @var string
     */
    private $update_url;

    /**
     * @var \DateTime
     */
    private $create_date;

    /**
     * @var \DateTime
     */
    private $update_date;

    /**
     * @var string
     */
    private $meta_robots;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $BlocPositions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->BlocPositions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set device_type_id
     *
     * @param  integer    $deviceTypeId
     * @return PageLayout
     */
    public function setDeviceTypeId($deviceTypeId)
    {
        $this->device_type_id = $deviceTypeId;

        return $this;
    }

    /**
     * Get device_type_id
     *
     * @return integer
     */
    public function getDeviceTypeId()
    {
        return $this->device_type_id;
    }

    /**
     * Set page_id
     *
     * @param  integer    $pageId
     * @return PageLayout
     */
    public function setPageId($pageId)
    {
        $this->page_id = $pageId;

        return $this;
    }

    /**
     * Get page_id
     *
     * @return integer
     */
    public function getPageId()
    {
        return $this->page_id;
    }

    /**
     * Set name
     *
     * @param  string     $name
     * @return PageLayout
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set url
     *
     * @param  string     $url
     * @return PageLayout
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set filename
     *
     * @param  string     $filename
     * @return PageLayout
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set header_chk
     *
     * @param  integer    $headerChk
     * @return PageLayout
     */
    public function setHeaderChk($headerChk)
    {
        $this->header_chk = $headerChk;

        return $this;
    }

    /**
     * Get header_chk
     *
     * @return integer
     */
    public function getHeaderChk()
    {
        return $this->header_chk;
    }

    /**
     * Set footer_chk
     *
     * @param  integer    $footerChk
     * @return PageLayout
     */
    public function setFooterChk($footerChk)
    {
        $this->footer_chk = $footerChk;

        return $this;
    }

    /**
     * Get footer_chk
     *
     * @return integer
     */
    public function getFooterChk()
    {
        return $this->footer_chk;
    }

    /**
     * Set edit_flg
     *
     * @param  integer    $editFlg
     * @return PageLayout
     */
    public function setEditFlg($editFlg)
    {
        $this->edit_flg = $editFlg;

        return $this;
    }

    /**
     * Get edit_flg
     *
     * @return integer
     */
    public function getEditFlg()
    {
        return $this->edit_flg;
    }

    /**
     * Set author
     *
     * @param  string     $author
     * @return PageLayout
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set description
     *
     * @param  string     $description
     * @return PageLayout
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set keyword
     *
     * @param  string     $keyword
     * @return PageLayout
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Set update_url
     *
     * @param  string     $updateUrl
     * @return PageLayout
     */
    public function setUpdateUrl($updateUrl)
    {
        $this->update_url = $updateUrl;

        return $this;
    }

    /**
     * Get update_url
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->update_url;
    }

    /**
     * Set create_date
     *
     * @param  \DateTime  $createDate
     * @return PageLayout
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param  \DateTime  $updateDate
     * @return PageLayout
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Set meta_robots
     *
     * @param  string     $metaRobots
     * @return PageLayout
     */
    public function setMetaRobots($metaRobots)
    {
        $this->meta_robots = $metaRobots;

        return $this;
    }

    /**
     * Get meta_robots
     *
     * @return string
     */
    public function getMetaRobots()
    {
        return $this->meta_robots;
    }

    /**
     * Add BlocPositions
     *
     * @param  \Eccube\Entity\BlocPosition $blocPositions
     * @return PageLayout
     */
    public function addBlocPosition(\Eccube\Entity\BlocPosition $blocPositions)
    {
        $this->BlocPositions[] = $blocPositions;

        return $this;
    }

    /**
     * Remove BlocPositions
     *
     * @param \Eccube\Entity\BlocPosition $blocPositions
     */
    public function removeBlocPosition(\Eccube\Entity\BlocPosition $blocPositions)
    {
        $this->BlocPositions->removeElement($blocPositions);
    }

    /**
     * Get BlocPositions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBlocPositions()
    {
        return $this->BlocPositions;
    }
}
