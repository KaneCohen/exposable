<?php

namespace ArjanWestdorp\Exposable\Signers;

use League\Uri\Http;
use League\Uri\Components\Query;
use League\Uri\Modifiers\KsortQuery;

abstract class BaseSigner
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var Http
     */
    protected $uri;

    /**
     * @var string
     */
    private $key;

    /**
     * Signer constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Check if the uri has the given key as parameter.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $query = new Query($this->uri->getQuery());

        return $query->hasPair($key);
    }

    /**
     * Get the count of query parameters in the url.
     *
     * @return int
     */
    public function parameters()
    {
        $query = new Query($this->uri->getQuery());

        return $query->count();
    }

    /**
     * Return a signed url.
     * The url will be signed with the given key.
     *
     * @return string
     */
    public function sign()
    {
        $this->sort();

        $this->add('signature', urlencode($this->getSignature($this->uri->__toString())));

        return $this->uri->__toString();
    }

    /**
     * Sort the parameters by key.
     *
     * @return $this
     */
    public function sort()
    {
        $modifier = new KsortQuery();

        $this->uri = $modifier->process($this->uri);

        return $this;
    }

    /**
     * Add parameter with given value to the query string.
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function add($key, $value)
    {
        $query = new Query($this->uri->getQuery());
        $query = $query->merge(Query::createFromPairs([
            $key => $value,
        ]));

        $this->uri = $this->uri->withQuery($query->__toString());

        return $this;
    }

    /**
     * Create signature for the given url.
     *
     * @param string $url
     * @return string
     */
    protected function getSignature($url)
    {
        return bin2hex(hash_hmac('sha256', $url, $this->key, true));
    }

    /**
     * Validate the given url based on the signature.
     *
     * @param string $url
     * @return bool
     */
    public function validate($url)
    {
        $signature = Signer::url($url)->get('signature');

        if (is_null($signature)) {
            return false;
        }

        $url = Signer::url($url)->delete('signature')->url();

        return hash_equals($signature, $this->getSignature($url));
    }

    /**
     * Get a parameter of the url based on it's key.
     *
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        $query = new Query($this->uri->getQuery());

        return $query->getPair($key);
    }

    /**
     * Set the url of the signer.
     * If no parameter is given return the url.
     *
     * @param string|null $url
     * @return string
     */
    public function url($url = null)
    {
        if (! is_null($url)) {
            $this->uri = Http::createFromString($url);

            return $this;
        }

        return $this->uri->__toString();
    }

    /**
     * Remove the given key from the query parameters.
     *
     * @param string $key
     * @return $this
     */
    public function delete($key)
    {
        $query = new Query($this->uri->getQuery());

        $without = $query->withoutPairs([$key]);

        $this->uri = $this->uri->withQuery($without->__toString());

        return $this;
    }
}
