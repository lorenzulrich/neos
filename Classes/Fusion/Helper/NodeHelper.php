<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Neos\Domain\Exception;

/**
 * Eel helper for ContentRepository Nodes
 */
class NodeHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
     * if no content collection could be found
     *
     * @throws Exception
     */
    public function nearestContentCollection(Node $node, string $nodePath): Node
    {
        $contentCollectionType = 'Neos.Neos:ContentCollection';
        if ($node->nodeType->isOfType($contentCollectionType)) {
            return $node;
        } else {
            if ($nodePath === '') {
                throw new Exception(sprintf(
                    'No content collection of type %s could be found in the current node and no node path was provided.'
                    . ' You might want to configure the nodePath property'
                    . ' with a relative path to the content collection.',
                    $contentCollectionType
                ), 1409300545);
            }
            $subNode = $this->findNodeByNodePath(
                $node,
                NodePath::fromString($nodePath)
            );

            if ($subNode !== null && $subNode->nodeType->isOfType($contentCollectionType)) {
                return $subNode;
            } else {
                $nodePathOfNode = $this->contentRepositoryRegistry->subgraphForNode($node)
                    ->findNodePath($node->nodeAggregateId);
                throw new Exception(sprintf(
                    'No content collection of type %s could be found in the current node (%s) or at the path "%s".'
                    . ' You might want to adjust your node type configuration and create the missing child node'
                    . ' through the "flow node:repair --node-type %s" command.',
                    $contentCollectionType,
                    $nodePathOfNode,
                    $nodePath,
                    $node->nodeType
                ), 1389352984);
            }
        }
    }

    /**
     * Generate a label for a node with a chaining mechanism. To be used in nodetype definitions.
     */
    public function labelForNode(Node $node): NodeLabelToken
    {
        return new NodeLabelToken($node);
    }

    public function inBackend(Node $node): bool
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        return !$nodeAddressFactory->createFromNode($node)->isInLiveWorkspace();
    }

    public function isLive(Node $node): bool
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        return $nodeAddressFactory->createFromNode($node)->isInLiveWorkspace();
    }

    /**
     * If this node type or any of the direct or indirect super types
     * has the given name.
     *
     * @param Node $node
     * @param string $nodeType
     * @return bool
     */
    public function isOfType(Node $node, string $nodeType): bool
    {
        return $node->nodeType->isOfType($nodeType);
    }


    public function nodeAddressToString(Node $node): string
    {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->subgraphIdentity->contentRepositoryId
        );
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        return $nodeAddressFactory->createFromNode($node)->serializeForUri();
    }

    private function findNodeByNodePath(Node $node, NodePath $nodePath): ?Node
    {
        if ($nodePath->isAbsolute()) {
            $node = $this->findRootNode($node);
        }

        return $this->findNodeByPath($node, $nodePath);
    }



    private function findRootNode(Node $node): Node
    {
        while (true) {
            $parentNode = $this->contentRepositoryRegistry->subgraphForNode($node)
                ->findParentNode($node->nodeAggregateId);
            if ($parentNode === null) {
                // there is no parent, so the root node was the node before
                return $node;
            } else {
                $node = $parentNode;
            }
        }
    }

    private function findNodeByPath(Node $node, NodePath $nodePath): ?Node
    {
        foreach ($nodePath->getParts() as $nodeName) {
            $childNode = $this->contentRepositoryRegistry->subgraphForNode($node)
                ->findChildNodeConnectedThroughEdgeName($node->nodeAggregateId, $nodeName);
            if ($childNode === null) {
                // we cannot find the child node, so there is no node on this path
                return null;
            }
            $node = $childNode;
        }

        return $node;
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
