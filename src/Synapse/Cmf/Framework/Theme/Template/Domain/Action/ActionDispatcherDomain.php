<?php

namespace Synapse\Cmf\Framework\Theme\Template\Domain\Action;

use Majora\Framework\Domain\ActionDispatcherDomain as MajoraActionDispatcherDomain;
use Majora\Framework\Domain\Action\ActionFactory;
use Synapse\Cmf\Framework\Theme\ContentType\Loader\LoaderInterface as ContentTypeLoader;
use Synapse\Cmf\Framework\Theme\ContentType\Model\ContentTypeInterface;
use Synapse\Cmf\Framework\Theme\Content\Entity\Content;
use Synapse\Cmf\Framework\Theme\Content\Model\ContentInterface;
use Synapse\Cmf\Framework\Theme\Content\Resolver\ContentResolver;
use Synapse\Cmf\Framework\Theme\TemplateType\Loader\LoaderInterface as TemplateTypeLoader;
use Synapse\Cmf\Framework\Theme\TemplateType\Model\TemplateTypeInterface;
use Synapse\Cmf\Framework\Theme\Template\Domain\DomainInterface;
use Synapse\Cmf\Framework\Theme\Template\Model\TemplateInterface;
use Synapse\Cmf\Framework\Theme\Zone\Entity\ZoneCollection;

/**
 * Template domain use cases class.
 */
class ActionDispatcherDomain extends MajoraActionDispatcherDomain implements DomainInterface
{
    /**
     * @var ContentResolver
     */
    protected $contentResolver;

    /**
     * @var ContentTypeLoader
     */
    protected $contentTypeLoader;

    /**
     * @var TemplateTypeLoader
     */
    protected $templateTypeLoader;

    /**
     * Construct.
     *
     * @param ActionFactory      $actionFactory
     * @param ContentResolver    $contentResolver
     * @param ContentTypeLoader  $contentTypeLoader
     * @param TemplateTypeLoader $templateTypeLoader
     */
    public function __construct(
        ActionFactory $actionFactory,
        ContentResolver $contentResolver,
        ContentTypeLoader  $contentTypeLoader,
        TemplateTypeLoader $templateTypeLoader
    ) {
        parent::__construct($actionFactory);

        $this->contentResolver = $contentResolver;
        $this->contentTypeLoader = $contentTypeLoader;
        $this->templateTypeLoader = $templateTypeLoader;
    }

    /**
     * Resolve given template type name as TemplateType object.
     *
     * @param mixed $templateType
     *
     * @return TemplateTypeInterface
     *
     * @throws InvalidArgumentException If template type isnt a TemplateTypeInterface or an unknown name
     */
    private function resolveTemplateType($templateType)
    {
        if (is_string($templateType)
            && !$templateType = $this->templateTypeLoader->retrieve($templateTypeName = $templateType)) {
            throw new \InvalidArgumentException(sprintf('Given template type name is invalid, "%s" given.',
                $templateTypeName
            ));
        }
        if ($templateType instanceof TemplateTypeInterface) {
            return $templateType;
        }

        throw new \InvalidArgumentException(sprintf(
            '%s() only supports template type names or template type objects, "%s" given.',
            __CLASS__,
            is_object($templateType) ? get_class($templateType) : $templateType
        ));
    }

    /**
     * @see DomainInterface::createLocal()
     */
    public function createLocal(ContentInterface $content, $templateType, ZoneCollection $zones = null)
    {
        // create and resolve promise
        return $this->getAction('create_local')
            ->setContent($this->contentResolver->resolve($content))
            ->setTemplateType($this->resolveTemplateType($templateType))
            ->setZones($zones ?: new ZoneCollection())
            ->resolve()
        ;
    }

    /**
     * @see DomainInterface::createGlobal()
     */
    public function createGlobal($contentType, $templateType, ZoneCollection $zones = null)
    {
        // resolve given content as content type
        switch (true) {

            case is_string($contentType) && is_a($contentType, ContentInterface::class, true):
                $contentType = $this->contentTypeTypeLoader->retrieveByContentClass($contentType);
                break;

            case $contentType instanceof Content:
                $contentType = $contentType->getType();
                break;

            case is_string($contentType):
                $contentType = $this->contentTypeLoader->retrieve($contentType);

            case $contentType instanceof ContentTypeInterface:
                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    '%s() only supports content class or object, or content object or name, "%s" given.',
                    __CLASS__,
                    is_object($contentType) ? get_class($contentType) : $contentType
                ));
        }

        // create and resolve promise
        return $this->getAction('create_global')
            ->setContentType($contentType)
            ->setTemplateType($this->resolveTemplateType($templateType))
            ->setZones($zones ?: new ZoneCollection())
            ->resolve()
        ;
    }

    /**
     * @see DomainInterface::edit()
     */
    public function edit(TemplateInterface $template, ...$arguments)
    {
        return $this->getAction('edit', $template, ...$arguments)
            ->resolve()
        ;
    }

    /**
     * @see DomainInterface::delete()
     */
    public function delete(TemplateInterface $template, ...$arguments)
    {
        return $this->getAction('delete', $template, ...$arguments)
            ->resolve()
        ;
    }
}
