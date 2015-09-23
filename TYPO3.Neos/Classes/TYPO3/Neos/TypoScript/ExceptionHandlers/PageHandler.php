<?php
namespace TYPO3\Neos\TypoScript\ExceptionHandlers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use TYPO3\Neos\TypoScript\ExceptionHandlers\ContextDependentHandler;
use TYPO3\Neos\Service\ContentElementWrappingService;

/**
 * A special exception handler that is used on the outer path to catch all unhandled exceptions and uses other exception
 * handlers depending on the login status.
 */
class PageHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var AccessDecisionManagerInterface
     */
    protected $accessDecisionManager;

    /**
     * @Flow\Inject
     * @var ContentElementWrappingService
     */
    protected $contentElementWrappingService;

    /**
     * Handle an exception by displaying an error message inside the Neos backend, if logged in and not displaying the live workspace.
     *
     * @param array $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        $handler = new ContextDependentHandler();
        $handler->setRuntime($this->runtime);
        $output = $handler->handleRenderingException($typoScriptPath, $exception);
        $currentContext = $this->runtime->getCurrentContext();
        /** @var NodeInterface $documentNode */
        $documentNode = isset($currentContext['documentNode']) ? $currentContext['documentNode'] : null;

        /** @var NodeInterface $node */
        $node = isset($currentContext['node']) ? $currentContext['node'] : null;

        $fluidView = $this->prepareFluidView();
        $isBackend = false;
        /** @var NodeInterface $siteNode */
        $siteNode = isset($currentContext['site']) ? $currentContext['site'] : null;

        if ($documentNode === null) {
            // Actually we cannot be sure that $node is a document. But for fallback purposes this should be safe.
            $documentNode = $siteNode ? $siteNode : $node;
        }

        if ($documentNode !== null && $documentNode->getContext()->getWorkspace()->getName() !== 'live' && $this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess')) {
            $isBackend = true;
            $fluidView->assign('metaData', $this->contentElementWrappingService->wrapContentObject($documentNode, $typoScriptPath, '<div id="neos-document-metadata"></div>', true));
        }

        $fluidView->assignMultiple(array(
            'isBackend' => $isBackend,
            'message' => $output,
            'node' => $node
        ));

        return $fluidView->render();
    }

    /**
     * Prepare fluid view for rendering error page with neos backend
     *
     * @return \TYPO3\Fluid\View\StandaloneView
     */
    protected function prepareFluidView()
    {
        $fluidView = new \TYPO3\Fluid\View\StandaloneView();
        $fluidView->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Error/NeosBackendMessage.html');
        $fluidView->setLayoutRootPath('resource://TYPO3.Neos/Private/Layouts');
        // FIXME find a better way than using templates as partials
        $fluidView->setPartialRootPath('resource://TYPO3.Neos/Private/Templates/TypoScriptObjects');
        $fluidView->setFormat('html');
        return $fluidView;
    }
}
