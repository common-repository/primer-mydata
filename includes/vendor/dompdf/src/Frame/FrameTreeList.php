<?php
namespace PrimerDompdf\Frame;

use IteratorAggregate;
use PrimerDompdf\Frame;

/**
 * Pre-order IteratorAggregate
 *
 * @access private
 * @package dompdf
 */
class FrameTreeList implements IteratorAggregate
{
    /**
     * @var Frame
     */
    protected $_root;

    /**
     * @param Frame $root
     */
    public function __construct(Frame $root)
    {
        $this->_root = $root;
    }

    /**
     * @return FrameTreeIterator
     */
    public function getIterator(): FrameTreeIterator
    {
        return new FrameTreeIterator($this->_root);
    }
}
