<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Customer\Attribute;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Test\Fixture\CustomerAttribute;
use Magento\EavGraphQl\Model\Uid;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test catalog EAV attributes metadata retrieval via GraphQL API
 */
class FileTest extends GraphQlAbstract
{
    private const QUERY = <<<QRY
{
  attributesMetadata(attributes: [{attribute_code: "%s", entity_type: "%s"}]) {
    items {
      uid
      code
      label
      entity_type
      frontend_input
      is_required
      default_value
      is_unique
      ... on CustomerAttributeMetadata {
        validate_rules {
          name
          value
        }
      }
    }
    errors {
      type
      message
    }
  }
}
QRY;

    #[
        DataFixture(
            CustomerAttribute::class,
            [
                'entity_type_id' => CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'frontend_input' => 'file',
                'validate_rules' => '{"MAX_FILE_SIZE":"10000000","FILE_EXTENSIONS":"PDF"}'
            ],
            'attribute'
        )
    ]
    public function testMetadata(): void
    {
        /** @var AttributeMetadataInterface $attribute */
        $attribute = DataFixtureStorageManager::getStorage()->get('attribute');

        $uid = Bootstrap::getObjectManager()->get(Uid::class)->encode(
            'customer',
            $attribute->getAttributeCode()
        );

        $formattedValidationRules = Bootstrap::getObjectManager()->get(FormatValidationRulesCommand::class)->execute(
            $attribute->getValidationRules()
        );

        $result = $this->graphQlQuery(sprintf(self::QUERY, $attribute->getAttributeCode(), 'customer'));

        $this->assertEquals(
            [
                'attributesMetadata' => [
                    'items' => [
                        [
                            'uid' => $uid,
                            'code' => $attribute->getAttributeCode(),
                            'label' => $attribute->getFrontendLabel(),
                            'entity_type' => 'CUSTOMER',
                            'frontend_input' => 'FILE',
                            'is_required' => false,
                            'default_value' => $attribute->getDefaultValue(),
                            'is_unique' => false,
                            'validate_rules' => $formattedValidationRules
                        ]
                    ],
                    'errors' => []
                ]
            ],
            $result
        );
    }
}
