<?php
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2010 John Finlay
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class sitemap_WT_Module extends WT_Module implements WT_Module_Config {
	const RECORDS_PER_VOLUME=1000;    // Keep sitemap files small, for memory, CPU and max_allowed_packet limits.
	const CACHE_LIFE        =1209600; // Two weeks
	
	// Extend WT_Module
	public function getTitle() {
		return /* I18N: Name of a module - see http://en.wikipedia.org/wiki/Sitemaps */ WT_I18N::translate('Sitemaps');
	}

	// Extend WT_Module
	public function getDescription() {
		return /* I18N: Description of the "Sitemaps" module */ WT_I18N::translate('Generate sitemap files for search engines.');
	}

	// Extend WT_Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'admin':
			$this->admin();
			break;
		case 'generate':
			$this->generate(safe_GET('file'));
			break;
		default:
			header('HTTP/1.0 404 Not Found');
		}
	}

	private function generate($file) {
		if ($file=='sitemap.xml') {
			$this->generate_index();
		} elseif (preg_match('/^sitemap-(\d+)-([ifsrmn])-(\d+).xml$/', $file, $match)) {
			$this->generate_file($match[1], $match[2], $match[3]);
		} else {
			header('HTTP/1.0 404 Not Found');
		}
	}

	// The index file contains references to all the other files
	private function generate_index() {
		// Check the cache
		$timestamp=get_module_setting($this->getName(), 'sitemap.timestamp');
		if ($timestamp > time()-self::CACHE_LIFE) {
			$data=get_module_setting($this->getName(), 'sitemap.xml');
		} else {
			$data='';
			$lastmod='<lastmod>'.date('Y-m-d').'</lastmod>';
			foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
				if (get_gedcom_setting($ged_id, 'include_in_sitemap')) {
					$n=WT_DB::prepare("SELECT COUNT(*) FROM `##individuals` WHERE i_file=?")->execute(array($ged_id))->fetchOne();
					for ($i=0; $i<=$n/self::RECORDS_PER_VOLUME; ++$i) {
						$data.='<sitemap><loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap-'.$ged_id.'-i-'.$i.'.xml</loc>'.$lastmod.'</sitemap>'.PHP_EOL;
					}
					$n=WT_DB::prepare("SELECT COUNT(*) FROM `##families` WHERE f_file=?")->execute(array($ged_id))->fetchOne();
					for ($i=0; $i<=$n/self::RECORDS_PER_VOLUME; ++$i) {
						$data.='<sitemap><loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap-'.$ged_id.'-f-'.$i.'.xml</loc>'.$lastmod.'</sitemap>'.PHP_EOL;
					}
					$n=WT_DB::prepare("SELECT COUNT(*) FROM `##sources` WHERE s_file=?")->execute(array($ged_id))->fetchOne();
					if ($n) {
						for ($i=0; $i<=$n/self::RECORDS_PER_VOLUME; ++$i) {
							$data.='<sitemap><loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap-'.$ged_id.'-s-'.$i.'.xml</loc>'.$lastmod.'</sitemap>'.PHP_EOL;
						}
					}
					$n=WT_DB::prepare("SELECT COUNT(*) FROM `##other` WHERE o_file=? AND o_type='REPO'")->execute(array($ged_id))->fetchOne();
					if ($n) {
						for ($i=0; $i<=$n/self::RECORDS_PER_VOLUME; ++$i) {
							$data.='<sitemap><loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap-'.$ged_id.'-r-'.$i.'.xml</loc>'.$lastmod.'</sitemap>'.PHP_EOL;
						}
					}
					$n=WT_DB::prepare("SELECT COUNT(*) FROM `##other` WHERE o_file=? AND o_type='NOTE'")->execute(array($ged_id))->fetchOne();
					if ($n) {
						for ($i=0; $i<=$n/self::RECORDS_PER_VOLUME; ++$i) {
							$data.='<sitemap><loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap-'.$ged_id.'-n-'.$i.'.xml</loc>'.$lastmod.'</sitemap>'.PHP_EOL;
						}
					}
					$n=WT_DB::prepare("SELECT COUNT(*) FROM `##media` WHERE m_gedfile=?")->execute(array($ged_id))->fetchOne();
					if ($n) {
						for ($i=0; $i<=$n/self::RECORDS_PER_VOLUME; ++$i) {
							$data.='<sitemap><loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap-'.$ged_id.'-m-'.$i.'.xml</loc>'.$lastmod.'</sitemap>'.PHP_EOL;
						}
					}
				}
			}
			$data='<'.'?xml version="1.0" encoding="UTF-8" ?'.'>'.PHP_EOL.'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL.$data.'</sitemapindex>'.PHP_EOL;
			// Cache this data
			set_module_setting($this->getName(), 'sitemap.xml', $data);
			set_module_setting($this->getName(), 'sitemap.timestamp', time());
		}
		header('Content-Type: application/xml');
		header('Content-Length: '.strlen($data));
		echo $data;
	}

	// A separate file for each family tree and each record type.
	private function generate_file($ged_id, $rec_type, $volume) {
		// Check the cache
		$timestamp=get_module_setting($this->getName(), 'sitemap-'.$ged_id.'-'.$rec_type.'-'.$volume.'.timestamp');
		if ($timestamp > time()-self::CACHE_LIFE) {
			$data=get_module_setting($this->getName(), 'sitemap-'.$ged_id.'-'.$rec_type.'-'.$volume.'.xml');
		} else {
			$data='';
			$records=array();
			switch ($rec_type) {
			case 'i':
				$rows=WT_DB::prepare(
					"SELECT 'INDI' AS type, i_id AS xref, i_file AS ged_id, i_gedcom AS gedrec".
					" FROM `##individuals`".
					" WHERE i_file=?".
					" ORDER BY i_id".
					" LIMIT ".self::RECORDS_PER_VOLUME." OFFSET ".($volume*self::RECORDS_PER_VOLUME)
				)->execute(array($ged_id))->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) {
					$records[]=WT_Person::getInstance($row);
				}
				break;
			case 'f':
				$rows=WT_DB::prepare(
					"SELECT 'FAM' AS type, f_id AS xref, f_file AS ged_id, f_gedcom AS gedrec".
					" FROM `##families`".
					" WHERE f_file=?".
					" ORDER BY f_id".
					" LIMIT ".self::RECORDS_PER_VOLUME." OFFSET ".($volume*self::RECORDS_PER_VOLUME)
				)->execute(array($ged_id))->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) {
					$records[]=WT_Family::getInstance($row);
				}
				break;
			case 's':
				$rows=WT_DB::prepare(
					"SELECT 'SOUR' AS type, s_id AS xref, s_file AS ged_id, s_gedcom AS gedrec".
					" FROM `##sources`".
					" WHERE s_file=?".
					" ORDER BY s_id".
					" LIMIT ".self::RECORDS_PER_VOLUME." OFFSET ".($volume*self::RECORDS_PER_VOLUME)
				)->execute(array($ged_id))->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) {
					$records[]=WT_Source::getInstance($row);
				}
				break;
			case 'r':
				$rows=WT_DB::prepare(
					"SELECT 'SOUR' AS type, o_id AS xref, o_file AS ged_id, o_gedcom AS gedrec".
					" FROM `##other`".
					" WHERE o_file=? AND o_type='REPO'".
					" ORDER BY o_id".
					" LIMIT ".self::RECORDS_PER_VOLUME." OFFSET ".($volume*self::RECORDS_PER_VOLUME)
				)->execute(array($ged_id))->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) {
					$records[]=WT_Repository::getInstance($row);
				}
				break;
			case 'n':
				$rows=WT_DB::prepare(
					"SELECT 'SOUR' AS type, o_id AS xref, o_file AS ged_id, o_gedcom AS gedrec".
					" FROM `##other`".
					" WHERE o_file=? AND o_type='NOTE'".
					" ORDER BY o_id".
					" LIMIT ".self::RECORDS_PER_VOLUME." OFFSET ".($volume*self::RECORDS_PER_VOLUME)
				)->execute(array($ged_id))->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) {
					$records[]=WT_Note::getInstance($row);
				}
				break;
			case 'm':
				$rows=WT_DB::prepare(
					"SELECT 'OBJE' AS type, m_media AS xref, m_gedfile AS ged_id, m_gedrec AS gedrec, m_titl, m_file".
					" FROM `##media`".
					" WHERE m_gedfile=?".
					" ORDER BY m_media".
					" LIMIT ".self::RECORDS_PER_VOLUME." OFFSET ".($volume*self::RECORDS_PER_VOLUME)
				)->execute(array($ged_id))->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) {
					$records[]=WT_Media::getInstance($row);
				}
				break;
			}
			foreach ($records as $record) {
				if ($record->canDisplayName()) {
					$data.='<url>';
					$data.='<loc>'.WT_SERVER_NAME.WT_SCRIPT_PATH.$record->getHtmlUrl().'</loc>';
					$chan=$record->getChangeEvent();
					if ($chan) {
						$date=$chan->getDate();
						if ($date->isOK()) {
							$data.='<lastmod>'.$date->minDate()->Format('%Y-%m-%d').'</lastmod>';
						}
					}
					$data.='</url>'.PHP_EOL;
				}
			}
			$data='<'.'?xml version="1.0" encoding="UTF-8" ?'.'>'.PHP_EOL.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'.PHP_EOL.$data.'</urlset>'.PHP_EOL;
			// Cache this data
			set_module_setting($this->getName(), 'sitemap-'.$ged_id.'-'.$rec_type.'-'.$volume.'.xml', $data);
			set_module_setting($this->getName(), 'sitemap-'.$ged_id.'-'.$rec_type.'-'.$volume.'.timestamp', time());
		}
		header('Content-Type: application/xml');
		header('Content-Length: '.strlen($data));
		echo $data;
	}

	private function admin() {
		$controller=new WT_Controller_Base();
		$controller
			->requireAdminLogin()
			->setPageTitle($this->getTitle())
			->pageHeader();

		// Save the updated preferences
		if (safe_POST('action', 'save')=='save') {
			foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
				set_gedcom_setting($ged_id, 'include_in_sitemap', safe_POST_bool('include'.$ged_id));
			}
			// Clear cache and force files to be regenerated
			WT_DB::prepare(
				"DELETE FROM `##module_setting` WHERE setting_name LIKE 'sitemap%'"
			)->execute();
		}

		$include_any=false;
		echo
			'<h3>', $this->getTitle(), '</h3>',
			'<p>',
			/* I18N: The www.sitemaps.org site is translated into many languages (e.g. http://www.sitemaps.org/fr/) - choose an appropriate URL. */
			WT_I18N::translate('Sitemaps are a way for webmasters to tell search engines about the pages on a website that are available for crawling.  All major search engines support sitemaps.  For more information, see <a href="http://www.sitemaps.org/">www.sitemaps.org</a>.').
			'</p>',
			'<p>', WT_I18N::translate('Which family trees should be included in the sitemaps?'), '</p>',
			'<form method="post" action="">',
			'<input type="hidden" name="action" value="save">';
		foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
			echo '<p><input type="checkbox" name="include', $ged_id, '"';
			if (get_gedcom_setting($ged_id, 'include_in_sitemap')) {
				echo ' checked="checked"';
				$include_any=true;
			}
			echo '> ', get_gedcom_setting($ged_id, 'title'), '</p>';
		}
		echo
			'<input type="submit" value="', WT_I18N::translate('Save'), '">',
			'</form>',
			'<hr>';

		if ($include_any) {
			$site_map_url1=WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&amp;mod_action=generate&amp;file=sitemap.xml';
			$site_map_url2=rawurlencode(WT_SERVER_NAME.WT_SCRIPT_PATH.'module.php?mod='.$this->getName().'&mod_action=generate&file=sitemap.xml');
			echo '<p>', WT_I18N::translate('To tell search engines that sitemaps are available, you should add the following line to your robots.txt file.'), '</p>';
			echo
				'<pre>Sitemap: ', $site_map_url1, '</pre>',
				'<hr>',
				'<p>', WT_I18N::translate('To tell search engines that sitemaps are available, you can use the following links.'), '</p>',
				'<ul>',
				// This list comes from http://en.wikipedia.org/wiki/Sitemaps
				'<li><a target="_new" href="http://submissions.ask.com/ping?sitemap='.$site_map_url2.'">Ask</a></li>',
				'<li><a target="_new" href="http://www.bing.com/webmaster/ping.aspx?siteMap='.$site_map_url2.'">Bing</a></li>',
				'<li><a target="_new" href="http://www.google.com/webmasters/tools/ping?sitemap='.$site_map_url2.'">Google</a></li>',
				'</ul>';

		}
	}

	// Implement WT_Module_Config
	public function getConfigLink() {
		return 'module.php?mod='.$this->getName().'&amp;mod_action=admin';
	}
}
