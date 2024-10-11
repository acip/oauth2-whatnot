<?php

namespace Acip\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class WhatnotResourceOwner implements ResourceOwnerInterface
{
    protected $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        // Replace 'id' with the key of the user's ID in the response
        return $this->response['id'];
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->response;
    }
}
