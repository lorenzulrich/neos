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

namespace Neos\Neos\Domain\Repository;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Neos\Domain\Model\SiteNodeName;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 * @method QueryResultInterface|Site[] findByNodeName(string $nodeName)
 * @method QueryResultInterface|Site[] findByState(int $state)
 */
class SiteRepository extends Repository
{
    /**
     * @var array<string,string>
     */
    protected $defaultOrderings = [
        'name' => QueryInterface::ORDER_ASCENDING,
        'nodeName' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="defaultSiteNodeName")
     * @var string
     */
    protected $defaultSiteNodeName;

    /**
     * Finds the first site
     *
     * @return Site The first site or NULL if none exists
     * @api
     */
    public function findFirst(): ?Site
    {
        /** @var ?Site $result */
        $result = $this->createQuery()->execute()->getFirst();

        return $result;
    }

    /**
     * Find all sites with status "online"
     *
     * @return QueryResultInterface<Site>
     */
    public function findOnline(): QueryResultInterface
    {
        return $this->findByState(Site::STATE_ONLINE);
    }

    /**
     * Find first site with status "online"
     */
    public function findFirstOnline(): ?Site
    {
        /** @var ?Site $site */
        $site = $this->findOnline()->getFirst();

        return $site;
    }

    public function findOneByNodeName(string|SiteNodeName $nodeName): ?Site
    {
        $query = $this->createQuery();
        /** @var ?Site $site */
        $site = $query->matching(
            $query->equals('nodeName', $nodeName)
        )
            ->execute()
            ->getFirst();

        return $site;
    }

    public function findSiteBySiteNode(Node $siteNode): Site
    {
        if ($siteNode->nodeName === null) {
            throw new \Neos\Neos\Domain\Exception(sprintf('Site node "%s" is unnamed', $siteNode->nodeAggregateId->value), 1681286146);
        }
        return $this->findOneByNodeName(SiteNodeName::fromNodeName($siteNode->nodeName))
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->nodeName->value), 1677245517);
    }

    /**
     * Find the site that was specified in the configuration ``defaultSiteNodeName``
     *
     * If the defaultSiteNodeName-setting is null the first active site is returned
     * If the site is not found or not active an exception is thrown
     *
     * @throws NeosException
     */
    public function findDefault(): ?Site
    {
        if ($this->defaultSiteNodeName === null) {
            return $this->findFirstOnline();
        }

        $defaultSite = $this->findOneByNodeName($this->defaultSiteNodeName);
        if (!$defaultSite instanceof Site || $defaultSite->getState() !== Site::STATE_ONLINE) {
            throw new NeosException(sprintf(
                'DefaultSiteNode %s not found or not active',
                $this->defaultSiteNodeName
            ), 1476374818);
        }
        return $defaultSite;
    }
}
