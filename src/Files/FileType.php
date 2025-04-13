<?php

namespace Symphograph\Bicycle\Files;

enum FileType: string
{
    case Img = 'img';
    case Doc = 'doc';
    case Audio = 'audio';
    case Video = 'video';
    case Archive = 'archive';


}
