<?php
namespace PrimerDompdf\Css\Content;

final class NoOpenQuote extends ContentPart
{
    public function __toString(): string
    {
        return "no-open-quote";
    }
}
