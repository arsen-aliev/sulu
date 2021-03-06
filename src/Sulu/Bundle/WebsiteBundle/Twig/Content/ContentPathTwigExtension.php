<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * provides the content_path function to generate real urls for frontend.
 */
class ContentPathTwigExtension extends \Twig_Extension implements ContentPathInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        ContentMapperInterface $contentMapper,
        WebspaceManagerInterface $webspaceManager,
        $environment,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->contentMapper = $contentMapper;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_content_path', [$this, 'getContentPath']),
            new \Twig_SimpleFunction('sulu_content_root_path', [$this, 'getContentRootPath']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getContentPath($url, $webspaceKey = null, $locale = null, $domain = null, $scheme = null)
    {
        if (!$this->requestAnalyzer) {
            return $url;
        }

        $domain = $domain ?: $this->requestAnalyzer->getAttribute('host');
        $scheme = $scheme ?: $this->requestAnalyzer->getAttribute('scheme');
        $locale = $locale ?: $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        if ($webspaceKey !== null) {
            if (!$this->webspaceManager->findWebspaceByKey($webspaceKey)->hasDomain($domain, $this->environment)) {
                $domain = null;
            }

            $url = $this->webspaceManager->findUrlByResourceLocator(
                $url,
                $this->environment,
                $locale,
                $webspaceKey,
                $domain,
                $scheme
            );
        } elseif (strpos($url, '/') === 0) {
            $url = $this->webspaceManager->findUrlByResourceLocator(
                $url,
                $this->environment,
                $locale,
                $this->requestAnalyzer->getWebspace()->getKey(),
                $domain,
                $scheme
            );
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentRootPath($full = false)
    {
        return $this->getContentPath('/');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_content_path';
    }
}
