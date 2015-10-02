<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractCollectionImplementation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * TypoScript implementation to render ContentCollections. Will render needed
 * metadata for removed nodes.
 */
class ContentCollectionImplementation extends AbstractCollectionImplementation
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
     */
    protected $accessDecisionManager;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
     */
    protected $nodeFactory;

    /**
     * Returns the identifier of the content collection node which shall be rendered
     *
     * @return string
     */
    public function getNodePath()
    {
        return $this->tsValue('nodePath');
    }

    /**
     * @return string
     */
    public function getTagName()
    {
        $tagName = $this->tsValue('tagName');
        if ((string)$tagName === '') {
            return 'div';
        } else {
            return $tagName;
        }
    }

    /**
     * @return NodeInterface
     */
    public function getContentCollectionNode()
    {
        $currentContext = $this->tsRuntime->getCurrentContext();
        return $currentContext['contentCollectionNode'];
    }

    /**
     * @return array
     */
    public function getCollection()
    {
        $contentCollectionNode = $this->getContentCollectionNode();
        if ($contentCollectionNode === null) {
            return array();
        }

        if ($contentCollectionNode->getContext()->getWorkspaceName() === 'live') {
            return $contentCollectionNode->getChildNodes();
        }

        return array_merge($contentCollectionNode->getChildNodes(), $this->getRemovedChildNodes());
    }

    /**
     * Render the list of nodes, and if there are none and we are not inside the live
     * workspace, render a button to create new content.
     *
     * @return string
     */
    public function evaluate()
    {
        $output = '<' . $this->getTagName() . $this->tsValue('attributes') . '>';
        $output .= parent::evaluate();
        $output .= '</' . $this->getTagName() . '>';
        return $output;
    }

    /**
     * Retrieves the removed nodes for this content collection so the user interface can publish removed nodes as well.
     *
     * @return array
     */
    protected function getRemovedChildNodes()
    {
        $contentCollectionNode = $this->getContentCollectionNode();
        if ($contentCollectionNode === null) {
            return array();
        }

        $contextProperties = $contentCollectionNode->getContext()->getProperties();
        $contextProperties['removedContentShown'] = true;

        $removedNodesContext = $this->contextFactory->create($contextProperties);
        // FIXME: Cleanly capsulate this and not use nodeDataRepository. childNodes() won't help as we need ONLY removed nodes here.
        $nodeDataElements = $this->nodeDataRepository->findByParentAndNodeType($contentCollectionNode->getPath(), '', $contentCollectionNode->getContext()->getWorkspace(), $contentCollectionNode->getContext()->getDimensions(), true, false);
        $finalNodes = array();
        foreach ($nodeDataElements as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $removedNodesContext);
            if ($node !== null) {
                $finalNodes[] = $node;
            }
        }

        return $finalNodes;
    }
}
