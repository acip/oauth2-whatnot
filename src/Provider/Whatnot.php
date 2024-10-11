<?php

namespace Acip\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class Whatnot extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $scopes = [];

    public $stagebaseUrl = 'https://stage-api.whatnot.com/seller-api';
    public $liveBaseUrl = 'https://api.whatnot.com/seller-api';

    public function __construct(
        array $options = [],
        array $collaborators = [],
        public WhatnotMode $mode = WhatnotMode::STAGE
    ) {
        parent::__construct($options, $collaborators);
    }

    public function baseUrl(): string
    {
        return match ($this->mode) {
            WhatnotMode::STAGE => $this->stagebaseUrl,
            WhatnotMode::LIVE => $this->liveBaseUrl,
        };
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->baseUrl() . '/rest/oauth/authorize';
    }

    /**
     * @inheritdoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->baseUrl() . '/rest/oauth/token';
    }

    /**
     * @inheritdoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->baseUrl() . '/graphql';
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    // /**
    //  * @inheritdoc
    //  */
    // protected function getDefaultHeaders()
    // {
    //     return [
    //         'Content-Type' => 'application/json',
    //         'Accept' => 'application/json',
    //     ];
    // }

    /**
     * @inheritdoc
     */
    public function getRequest($method, $url, array $options = [])
    {
        // client secret is required for unauthenticated requests
        return $this->createRequest($method, $url, $this->clientSecret, $options);
    }

    /**
     * @inheritdoc
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['errors'])) {
            // all errors concatenated
            $error = \implode(', ', \array_map(function ($error) {
                return $error['message'];
            }, $data['errors']));

            throw new IdentityProviderException(
                $error,
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);
        $options = [
            'body' => \json_encode([
                'query' => 'query {
                    me {
                        id
                        username
                        displayName
                        currencyCode
                        countryCode
                        features {
                        auctions
                        }
                    }
                }'
            ]),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ];

        $request = $this->getAuthenticatedRequest(self::METHOD_POST, $url, $token, $options);

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        if (false === isset($response['data']['me'])) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected data.me.'
            );
        }

        return $response['data']['me'];
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        // Create and return a resource owner instance
        return new WhatnotResourceOwner($response);
    }
}
