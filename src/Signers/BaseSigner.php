<?php

namespace ArjanWestdorp\Exposable\Signers;

use League\Uri\Http;
use ArjanWestdorp\Exposable\Signers\Internal\SigningQuery;

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

    private bool $emptyQuery = false;

    private bool $emptyFragment = false;

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
        $query = SigningQuery::new($this->uri->getQuery());

        return $query->has($key);
    }

    /**
     * Get the count of distinct query parameter keys in the url.
     *
     * @return int
     */
    public function parameters()
    {
        $query = SigningQuery::new($this->uri->getQuery());

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

        $this->add('signature', urlencode($this->getSignature($this->serializedUrl())));

        return $this->serializedUrl();
    }

    /**
     * Sort the parameters by key.
     *
     * @return $this
     */
    public function sort()
    {
        $query = SigningQuery::new($this->uri->getQuery())->sort();
        $this->emptyQuery = false;
        $this->uri = $this->uri->withQuery((string) $query);

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
        $query = SigningQuery::new($this->uri->getQuery());
        $query = $query->add($key, $value);

        $this->emptyQuery = false;
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
        $candidate = (new static($this->key))->url($url);
        $signature = $candidate->get('signature');

        if (! is_string($signature)) {
            return false;
        }

        $url = $candidate->delete('signature')->url();

        return hash_equals($signature, $this->getSignature($url));
    }

    /**
     * Get a parameter of the url based on its key.
     *
     * @param string $key
     * @return string|array|null
     */
    public function get($key)
    {
        $query = SigningQuery::new($this->uri->getQuery());

        return $query->get($key);
    }

    /**
     * Set the url of the signer.
     * If no parameter is given return the url.
     *
     * @param string|null $url
     * @return $this|string
     */
    public function url($url = null)
    {
        if (! is_null($url)) {
            $this->uri = Http::new($url);
            $this->emptyQuery = '' === parse_url($url, PHP_URL_QUERY);
            $this->emptyFragment = '' === parse_url($url, PHP_URL_FRAGMENT);

            return $this;
        }

        return $this->serializedUrl();
    }

    private function serializedUrl(): string
    {
        $url = (string) $this->uri;
        if ($this->emptyQuery) {
            $fragment = strpos($url, '#');
            $url = false === $fragment ? $url.'?' : substr_replace($url, '?', $fragment, 0);
        }
        if ($this->emptyFragment) {
            $url .= '#';
        }

        return $url;
    }

    /**
     * Remove the given key from the query parameters.
     *
     * @param string $key
     * @return $this
     */
    public function delete($key)
    {
        $query = SigningQuery::new($this->uri->getQuery());

        $without = $query->delete($key);

        $this->emptyQuery = false;
        $this->uri = $this->uri->withQuery($without->__toString());

        return $this;
    }
}
