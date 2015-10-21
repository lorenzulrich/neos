<?php
namespace TYPO3\Neos\Controller\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * REST service for workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends ActionController
{
    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Service\UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => 'TYPO3\Fluid\View\TemplateView',
        'json' => 'TYPO3\Neos\View\Service\NodeJsonView'
    );

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * Shows a list of existing workspaces
     *
     * @param boolean $onlyPublishable
     * @return string
     */
    public function indexAction($onlyPublishable = false)
    {
        $workspaces = $this->workspaceRepository->findAll();
        if ($onlyPublishable) {
            $workspaces = $this->filterWorkspacesByPublishPrivilege($workspaces);
        }

        $this->view->assign('workspaces', $workspaces);
    }

    /**
     * Shows details of the given workspace
     *
     * @param Workspace $workspace
     * @return string
     */
    public function showAction(Workspace $workspace)
    {
        $this->view->assign('workspace', $workspace);
    }

    /**
     * Create a workspace
     *
     * @param string $workspaceName
     * @param Workspace $baseWorkspace
     * @param string $ownerAccountIdentifier
     * @return string
     */
    public function createAction($workspaceName, Workspace $baseWorkspace, $ownerAccountIdentifier = null)
    {
        $existingWorkspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        if ($existingWorkspace !== null) {
            $this->throwStatus(409, 'Workspace already exists', '');
        }

        if ($ownerAccountIdentifier !== null) {
            $owner = $this->userService->getUser($ownerAccountIdentifier);
            if ($owner === null) {
                $this->throwStatus(422, 'Requested owner account does not exist', '');
            }
        } else {
            $owner = null;
        }

        $workspace = new Workspace($workspaceName, $baseWorkspace, $owner);
        $this->workspaceRepository->add($workspace);
        $this->throwStatus(201, 'Workspace created', '');
    }

    /**
     * Configure property mapping for the updateAction
     *
     * @return void
     */
    public function initializeUpdateAction()
    {
        $propertyMappingConfiguration = $this->arguments->getArgument('workspace')->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowProperties('name', 'baseWorkspace');
        $propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
    }

    /**
     * Updates a workspace
     *
     * @param Workspace $workspace The updated workspace
     * @return void
     */
    public function updateAction(Workspace $workspace)
    {
        $this->workspaceRepository->update($workspace);
        $this->throwStatus(200, 'Workspace updated', '');
    }

    /**
     * Filters the given query result by publish privilege.
     *
     * @param QueryResultInterface $workspaces
     * @return array
     */
    protected function filterWorkspacesByPublishPrivilege(QueryResultInterface $workspaces)
    {
        $filteredWorkspaces = array_filter($workspaces->toArray(), [$this, 'filterWorkspaceByPublishPrivilege']);

        return $filteredWorkspaces;
    }

    /**
     * Check privilege to publish to the given workspace is granted.
     *
     * @param Workspace $workspace
     * @return boolean
     */
    protected function filterWorkspaceByPublishPrivilege(Workspace $workspace)
    {
        if ($workspace->getName() !== 'live') {
            return true;
        }

        if ($this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.PublishToLiveWorkspace')) {
            return true;
        }

        return false;
    }
}
