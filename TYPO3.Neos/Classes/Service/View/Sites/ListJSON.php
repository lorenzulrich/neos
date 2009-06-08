<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\View\Sites;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Service
 * @version $Id$
 */

/**
 * JSON view for the Sites List action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ListJSON extends \F3\FLOW3\MVC\View\AbstractView {

	/**
	 * @var array An array of sites
	 */
	public $sites = array();

	/**
	 * Renders this list view
	 *
	 * @return string The rendered JSON output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$sitesArray = array();
		foreach ($this->sites as $site) {
			$sitesArray[] = array(
				'id' => $site->getId(),
				'name' => $site->getName(),
				'siteRoot' => ($site->getSiteRoot() !== NULL) ? $site->getSiteRoot()->getId() : NULL
			);
		}
		return json_encode($sitesArray);
	}
}
?>