<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

use Zarathustra\Modlr\RestOdm\Exception\InvalidArgumentException;

/**
 * Primary REST request object.
 * Is created/parsed from a core Request object.
 *
 * @todo   Ensure thrown exceptions are API response friendly.
 * @author Jacob Bare <jbare@southcomm.com>
 */
class RestRequest
{
    const PARAM_INCLUSIONS = 'include';
    const PARAM_FIELDSETS  = 'fields';
    const PARAM_SORTING    = 'sort';
    const PARAM_PAGINATION = 'page';
    const PARAM_FILTERING  = 'filter';

    private $requestMethod;

    private $parsedUri;

    private $entityType;

    private $identifier;

    private $relationship = [];

    private $inclusions = [];

    private $sorting = [];

    private $fields = [];

    private $pagination = [
        'offset'    => 0,
        'limit'     => 50,
    ];

    private $filters = [];

    private $payload;

    private $config;

    /**
     * Constructor.
     *
     * @param   string      $method     The request method.
     * @param   string      $uri        The complete URI (URL) of the request, included scheme, host, path, and query string.
     * @param   string|null $payload    The request payload (body).
     */
    public function __construct(RestConfiguration $config, $method, $uri, $payload = null)
    {
        $this->config = $config;
        $this->requestMethod = strtoupper($method);
        $this->parse($uri);
        $this->payload = empty($payload) ? null : new RestPayload($payload);

        $this->config->setHost($this->getHost());
        $this->config->setScheme($this->getScheme());
    }

    public function getScheme()
    {
        return $this->parsedUri['scheme'];
    }

    public function getHost()
    {
        return $this->parsedUri['host'];
    }

    public function getMethod()
    {
        return $this->requestMethod;
    }

    public function getEntityType()
    {
        return $this->entityType;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function hasIdentifier()
    {
        return null !== $this->getIdentifier();
    }

    public function isRelationship()
    {
        return !empty($this->relationship);
    }

    public function hasInclusions()
    {
        $value = $this->getInclusions();
        return !empty($value);
    }

    public function getInclusions()
    {
        return $this->inclusions;
    }

    public function hasFieldset()
    {
        $value = $this->getFieldset();
        return !empty($value);
    }

    public function getFieldset()
    {
        return $this->fields;
    }

    public function hasSorting()
    {
        $value = $this->getSorting();
        return !empty($value);
    }

    public function getSorting()
    {
        return $this->sorting;
    }

    public function hasPagination()
    {
        $value = $this->getPagination();
        return !empty($value);
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function hasFilters()
    {
        return !empty($this->filters);
    }

    public function hasFilter($key)
    {
        return null !== $this->getFilter($key);
    }

    public function getFilter($key)
    {
        if (!isset($this->filters[$key])) {
            return null;
        }
        return $this->filters[$key];
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function hasPayload()
    {
        return $this->getPayload() instanceof RestPayload;
    }

    private function parse($uri)
    {
        $this->parsedUri = parse_url($uri);

        if (false === strstr($this->parsedUri['path'], $this->config->getRootEndpoint())) {
            throw RestException::invalidEndpoint($this->parsedUri['path']);
        }

        $this->parsedUri['path'] = str_replace($this->config->getRootEndpoint(), '', $this->parsedUri['path']);
        $this->parsePath($this->parsedUri['path']);

        $this->parsedUri['query'] = isset($this->parsedUri['query']) ? $this->parsedUri['query'] : '';
        $this->parseQueryString($this->parsedUri['query']);

        return $this;
    }

    private function parsePath($path)
    {
        $parts = explode('/', trim($path, '/'));
        for ($i = 0; $i < 1; $i++) {
            // All paths must contain /{workspace_entityType}
            if (false === $this->issetNotEmpty($i, $parts)) {
                throw RestException::invalidEndpoint($path);
            }
        }
        $this->extractEntityType($parts);
        $this->extractIdentifier($parts);
        $this->extractRelationship($parts);
    }

    private function extractEntityType(array $parts)
    {
        $this->entityType = $parts[0];
        return $this;
    }

    private function extractIdentifier(array $parts)
    {
        if (isset($parts[1])) {
            $this->identifier = $parts[1];
        }
        return $this;
    }

    private function extractRelationship(array $parts)
    {
        if (isset($parts[2])) {
            if ('relationships' === $parts[2]) {
                if (!isset($parts[3])) {
                    throw RestException::invalidRelationshipEndpoint($this->parsedUri['path']);
                }
                $this->relationship = [
                    'type'  => 'self',
                    'field' => $parts[3],
                ];
            } else {
                $this->relationship = [
                    'type'  => 'related',
                    'field' => $parts[2],
                ];
            }
        }
        return $this;
    }

    private function parseQueryString($queryString)
    {
        parse_str($queryString, $parsed);

        $supported = $this->getSupportedParams();
        foreach ($parsed as $param => $value) {
            if (!isset($supported[$param])) {
                throw RestException::unsupportedQueryParam($param, array_keys($supported));
            }
        }

        $this->extractInclusions($parsed);
        $this->extractSorting($parsed);
        $this->extractFields($parsed);
        $this->extractPagination($parsed);
        $this->extractFilters($parsed);

        return $this;
    }

    private function extractInclusions(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_INCLUSIONS, $params)) {
            return $this;
        }
        $this->inclusions = explode(',', $params[self::PARAM_INCLUSIONS]);
        return $this;
    }

    private function extractSorting(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_SORTING, $params)) {
            return $this;
        }
        $sort = explode(',', $params[self::PARAM_SORTING]);
        foreach ($sort as $field) {
            $direction = 1;
            if (0 === strpos($field, '-')) {
                $direction = -1;
                $field = str_replace('-', '', $field);
            }
            $this->sorting[$field] = $direction;
        }
        return $this;
    }

    private function extractFields(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_FIELDSETS, $params)) {
            return $this;
        }
        $fields = $params[self::PARAM_FIELDSETS];
        if (!is_array($fields)) {
            throw RestException::invalidQueryParam(self::PARAM_FIELDSETS, 'The field parameter must be an array of entity type keys to fields.');
        }
        foreach ($fields as $entityType => $string) {
            $this->fields[$entityType] = explode(',', $string);
        }
        return $this;
    }

    private function extractPagination(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_PAGINATION, $params)) {
            return $this;
        }
        $page = $params[self::PARAM_PAGINATION];
        if (!is_array($page) || !isset($page['limit'])) {
            throw RestException::invalidQueryParam(self::PARAM_PAGINATION, 'The page parameter must be an array containing at least a limit.');
        }
        $this->pagination = [
            'offset'    => isset($page['offset']) ? (Integer) $page['offset'] : 0,
            'limit'     => (Integer) $page['limit'],
        ];
        return $this;
    }

    private function extractFilters(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_FILTERING, $params)) {
            return $this;
        }
        $filters = $params[self::PARAM_FILTERING];
        if (!is_array($filters)) {
            throw RestException::invalidQueryParam(self::PARAM_FILTERING, 'The filter parameter must be an array keyed by filter name and value.');
        }
        foreach ($filters as $key => $value) {
            $this->filters[$key] = $value;
        }
        return $this;
    }

    public function getSupportedParams()
    {
        return [
            self::PARAM_INCLUSIONS  => true,
            self::PARAM_FIELDSETS   => true,
            self::PARAM_SORTING     => true,
            self::PARAM_PAGINATION  => true,
            self::PARAM_FILTERING   => true,
        ];
    }

    private function issetNotEmpty($key, $value)
    {
        return isset($value[$key]) && !empty($value[$key]);
    }
}
