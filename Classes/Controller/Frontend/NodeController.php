<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Frontend;

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
 * Controller for displaying nodes in the frontend
 *
 * @scope singleton
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var array
	 */
	protected $supportedFormats = array('html');

	/**
	 * @var array
	 */
	protected $defaultViewObjectName = 'F3\TYPO3\View\TypoScriptView';

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction(\F3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if (!$node->isAccessible()) {
			throw new \F3\FLOW3\Security\Exception\AuthenticationRequiredException();
		}
		if (!$node->isVisible()) {
			$this->throwStatus(404);
		}
		$node->getContext()->setCurrentNode($node);
		$this->view->assign('value', $node);

		$this->response->setHeader('Cache-Control', 'public, s-maxage=600', FALSE);
	}

}
?>