<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use PhpCollection\Map;
use PhpCollection\Sequence;

class PhpCollectionHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        $methods = array();
        $formats = array('json', 'xml', 'yml');
        $collectionTypes = array(
            'PhpCollection\Sequence' => 'Sequence',
            'PhpCollection\Map' => 'Map',
        );

        foreach ($collectionTypes as $type => $shortName) {
            foreach ($formats as $format) {
                $methods[] = array(
                    'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                    'type' => $type,
                    'format' => $format,
                    'method' => 'serialize'.$shortName,
                );

                $methods[] = array(
                    'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                    'type' => $type,
                    'format' => $format,
                    'method' => 'deserialize'.$shortName,
                );
            }
        }

        return $methods;
    }

    public function serializeMap(VisitorInterface $visitor, Map $map, array $type, Context $context)
    {
	//  Pop ourselves out of the context not to be counted as a depth level
        $context->stopVisiting($map);
        $type['name'] = 'array';
	$result = $visitor->visitArray(iterator_to_array($map), $type, $context);

        //  Push ourselves back in, so we can be popped after leaving the handler
        $context->startVisiting($map);

        return $result;
    }

    public function deserializeMap(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        $type['name'] = 'array';

        return new Map($visitor->visitArray($data, $type, $context));
    }

    public function serializeSequence(VisitorInterface $visitor, Sequence $sequence, array $type, Context $context)
    {
	//  Pop ourselves out of the context not to be counted as a depth level
        $context->stopVisiting($sequence);
        // We change the base type, and pass through possible parameters.
        $type['name'] = 'array';
	$result = $visitor->visitArray($sequence->all(), $type, $context);

        //  Push ourselves back in, so we can be popped after leaving the handler
        $context->startVisiting($sequence);

        return $result;
    }

    public function deserializeSequence(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        // See above.
        $type['name'] = 'array';

        return new Sequence($visitor->visitArray($data, $type, $context));
    }
}
