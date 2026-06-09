<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Fieldset;
use ValidFormBuilder\Hidden;
use ValidFormBuilder\MultiField;
use ValidFormBuilder\Page;
use ValidFormBuilder\Text;
use ValidFormBuilder\ValidForm;
use ValidFormBuilder\ValidWizard;

/**
 * Coverage for {@link \ValidFormBuilder\ValidWizard}.
 *
 * ValidWizard extends ValidForm for multi-page (wizard) form flows. It adds
 * page management, per-page validation, confirm-page support, and navigation
 * labels.
 *
 * Surface covered:
 * - Constructor: nextLabel, previousLabel from meta, delegates to parent.
 * - addPage(): creates Page, adds uniqueId hidden field on first page.
 * - addField / addFieldset: delegates to the last page, not the form root.
 * - getPage(): page retrieval by index.
 * - isSubmitted(): checks dispatch key + reads uniqueId from request.
 * - isValid(): full wizard or per-page validation.
 * - addConfirmPage / removeConfirmPage / hasConfirmPage.
 * - getFields(): flat field collection from all pages.
 *
 * Security audit:
 * - isSubmitted bypasses CSRF (unlike parent ValidForm) — documented as
 *   intentional for wizard flow compatibility. Potential concern for
 *   production deployments.
 * - No new XSS vectors (inherits parent's rendering).
 */
class ValidWizardTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (array_keys($_REQUEST) as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorSetsNameAndDelegatesToParent(): void
    {
        $wizard = new ValidWizard('signup', 'Sign up wizard');

        $this->assertSame('signup', $wizard->getName());
        $this->assertSame('Sign up wizard', $wizard->getDescription());
    }

    #[Test]
    public function constructorReadsNavigationLabelsFromMeta(): void
    {
        $wizard = new ValidWizard('signup', null, null, [
            'nextLabel' => 'Continue',
            'previousLabel' => 'Go back',
        ]);

        $this->assertSame('Continue', $wizard->getNextLabel());
        $this->assertSame('Go back', $wizard->getPreviousLabel());
    }

    #[Test]
    public function constructorDefaultsNavigationLabels(): void
    {
        $wizard = new ValidWizard('signup');

        // Default labels use HTML entities for arrows.
        $this->assertStringContainsString('Next', $wizard->getNextLabel());
        $this->assertStringContainsString('Previous', $wizard->getPreviousLabel());
    }

    // --------------------------------------------------------------
    // addPage
    // --------------------------------------------------------------

    #[Test]
    public function addPageCreatesPageAndReturnsIt(): void
    {
        $wizard = new ValidWizard('signup');
        $page = $wizard->addPage('step-1', 'Personal Info');

        $this->assertInstanceOf(Page::class, $page);
    }

    #[Test]
    public function firstPageGetsHiddenUniqueIdField(): void
    {
        $wizard = new ValidWizard('signup');
        $page = $wizard->addPage('step-1', 'Step 1');

        // The first addPage() injects a vf__uniqueid hidden field.
        $field = $wizard->getValidField('vf__uniqueid');
        $this->assertNotNull($field);
    }

    #[Test]
    public function multiplePagesDontDuplicateUniqueIdField(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'Step 1');
        $wizard->addPage('step-2', 'Step 2');

        // Only one vf__uniqueid field even with multiple pages.
        $count = 0;
        foreach ($wizard->getFields() as $field) {
            if ($field->getName() === 'vf__uniqueid') {
                $count++;
            }
        }
        $this->assertSame(1, $count);
    }

    // --------------------------------------------------------------
    // addField / addFieldset — delegate to last page
    // --------------------------------------------------------------

    #[Test]
    public function addFieldAddsToLastPage(): void
    {
        $wizard = new ValidWizard('signup');
        $page1 = $wizard->addPage('step-1', 'Step 1');
        $wizard->addField('first-name', 'First name', ValidForm::VFORM_STRING);

        $page2 = $wizard->addPage('step-2', 'Step 2');
        $wizard->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        // First name should be on page 1, email on page 2.
        $page1Fields = [];
        foreach ($page1->getFields() as $fs) {
            if ($fs instanceof Fieldset) {
                foreach ($fs->getFields() as $f) {
                    $page1Fields[] = $f->getName();
                }
            }
        }
        $this->assertContains('first-name', $page1Fields);
        $this->assertNotContains('email', $page1Fields);
    }

    #[Test]
    public function addFieldsetAddsToLastPage(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $fieldset = $wizard->addFieldset('Contact');

        $this->assertInstanceOf(Fieldset::class, $fieldset);
    }

    #[Test]
    public function addMultiFieldAddsToLastPage(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $multi = $wizard->addMultiField('Full name');

        $this->assertInstanceOf(MultiField::class, $multi);
    }

    // --------------------------------------------------------------
    // getPage
    // --------------------------------------------------------------

    #[Test]
    public function getPageReturnsPageByOneBasedIndex(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'First');
        $wizard->addPage('step-2', 'Second');

        $page = $wizard->getPage(2);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('Second', $page->getHeader());
    }

    #[Test]
    public function getPageReturnsFirstPageWhenIndexExceedsPageCount(): void
    {
        // KNOWN QUIRK: seek() silently does nothing for out-of-range indices,
        // so current() stays at whatever the pointer was (typically position 0).
        // getPage(99) with 1 page returns page 1 instead of null.
        $wizard = new ValidWizard('signup');
        $page1 = $wizard->addPage('step-1', 'First');

        $result = $wizard->getPage(99);

        $this->assertNotNull($result);
        $this->assertSame('First', $result->getHeader());
    }

    // --------------------------------------------------------------
    // isSubmitted
    // --------------------------------------------------------------

    #[Test]
    public function isSubmittedReturnsTrueWhenDispatchKeyMatches(): void
    {
        // ValidWizard::isSubmitted does NOT check CSRF (unlike the parent).
        $wizard = new ValidWizard('signup');
        $_REQUEST['vf__dispatch'] = 'signup';

        $this->assertTrue($wizard->isSubmitted());
    }

    #[Test]
    public function isSubmittedReturnsFalseWhenDispatchKeyDoesNotMatch(): void
    {
        $wizard = new ValidWizard('signup');
        $_REQUEST['vf__dispatch'] = 'other-form';

        $this->assertFalse($wizard->isSubmitted());
    }

    #[Test]
    public function isSubmittedCanBeForced(): void
    {
        $wizard = new ValidWizard('signup');

        $this->assertTrue($wizard->isSubmitted(true));
    }

    #[Test]
    public function isSubmittedReadsUniqueIdFromRequest(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');

        $originalId = $wizard->getUniqueId();

        $_REQUEST['vf__dispatch'] = 'signup';
        $_REQUEST['vf__uniqueid'] = 'custom-session-id';

        $wizard->isSubmitted();

        // After isSubmitted, the wizard should have picked up the uniqueId from request.
        $this->assertSame('custom-session-id', $wizard->getUniqueId());
    }

    // --------------------------------------------------------------
    // isValid
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsTrueForEmptyWizard(): void
    {
        $wizard = new ValidWizard('signup');

        $this->assertTrue($wizard->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWhenAPageFieldIsInvalid(): void
    {
        $wizard = new ValidWizard('signup');
        $page = $wizard->addPage('step-1');
        $wizard->addField('name', 'Name', ValidForm::VFORM_STRING, ['required' => true]);

        $this->assertFalse($wizard->isValid());
    }

    #[Test]
    public function isValidWithPageIdValidatesPagesBeforeTheTargetNotIncluding(): void
    {
        // isValidUntil() iterates pages and breaks when it REACHES the target
        // page — it validates everything BEFORE the target, not the target itself.
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $wizard->addField('name', 'Name', ValidForm::VFORM_STRING, ['required' => true]);

        $wizard->addPage('step-2');
        $wizard->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        // isValid('step-2') validates step-1 (required name empty → false).
        $this->assertFalse($wizard->isValid('step-2'));

        // isValid('step-1') validates nothing before step-1 → true.
        $this->assertTrue($wizard->isValid('step-1'));
    }

    // --------------------------------------------------------------
    // Confirm page
    // --------------------------------------------------------------

    #[Test]
    public function confirmPageDefaultsToFalse(): void
    {
        $wizard = new ValidWizard('signup');

        $this->assertFalse($wizard->hasConfirmPage());
    }

    #[Test]
    public function addConfirmPageSetsFlag(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addConfirmPage();

        $this->assertTrue($wizard->hasConfirmPage());
    }

    #[Test]
    public function removeConfirmPageClearsFlag(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addConfirmPage();
        $wizard->removeConfirmPage();

        $this->assertFalse($wizard->hasConfirmPage());
    }

    // --------------------------------------------------------------
    // getFields
    // --------------------------------------------------------------

    #[Test]
    public function getFieldsReturnsFlatCollectionAcrossAllPages(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $wizard->addField('name', 'Name', ValidForm::VFORM_STRING);
        $wizard->addPage('step-2');
        $wizard->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        $fields = $wizard->getFields();

        // At minimum: name + email + vf__uniqueid.
        $this->assertGreaterThanOrEqual(3, $fields->count());
    }

    // --------------------------------------------------------------
    // addMultiField / addField / addFieldset — auto-create page/fieldset
    // --------------------------------------------------------------

    #[Test]
    public function addMultiFieldCreatesFirstPageOnEmptyWizard(): void
    {
        $wizard = new ValidWizard('signup');
        $multi = $wizard->addMultiField('Full name');

        $this->assertInstanceOf(MultiField::class, $multi);
        $this->assertSame(1, $wizard->getPageCount());
    }

    #[Test]
    public function addMultiFieldCreatesFieldsetOnPageWithoutFieldset(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        // Page 2 has no fieldset yet (only the first page gets the implicit
        // vf__uniqueid fieldset), so addMultiField must create one.
        $page2 = $wizard->addPage('step-2');
        $multi = $wizard->addMultiField('Full name');

        $found = false;
        foreach ($page2->getFields() as $fieldset) {
            if ($fieldset instanceof Fieldset) {
                foreach ($fieldset->getFields() as $field) {
                    if ($field === $multi) {
                        $found = true;
                    }
                }
            }
        }
        $this->assertTrue($found);
    }

    #[Test]
    public function addFieldCreatesFirstPageOnEmptyWizard(): void
    {
        $wizard = new ValidWizard('signup');
        $field = $wizard->addField('name', 'Name', ValidForm::VFORM_STRING);

        $this->assertSame(1, $wizard->getPageCount());
        $this->assertSame($field, $wizard->getValidField('name'));
    }

    #[Test]
    public function addFieldsetCreatesFirstPageOnEmptyWizard(): void
    {
        $wizard = new ValidWizard('signup');
        $fieldset = $wizard->addFieldset('Contact');

        $this->assertInstanceOf(Fieldset::class, $fieldset);
        $this->assertSame(1, $wizard->getPageCount());
    }

    // --------------------------------------------------------------
    // getPage — edge cases
    // --------------------------------------------------------------

    #[Test]
    public function getPageClampsNonPositivePageNumberToFirstPage(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'First');
        $wizard->addPage('step-2', 'Second');

        $page = $wizard->getPage(0);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('First', $page->getHeader());
    }

    #[Test]
    public function getPageReturnsNullWhenWizardHasNoPages(): void
    {
        $wizard = new ValidWizard('signup');

        $this->assertNull($wizard->getPage(1));
    }

    // --------------------------------------------------------------
    // valuesAsHtml
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlRendersPageHeadersAndFieldValues(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'Step One');
        $wizard->addField('name', 'Name', ValidForm::VFORM_STRING);
        $wizard->addPage('step-2', 'Step Two');
        $wizard->addField('city', 'City', ValidForm::VFORM_STRING);

        $_REQUEST['vf__dispatch'] = 'signup';
        $_REQUEST['name'] = 'Robin';
        $_REQUEST['city'] = 'Amsterdam';
        $wizard->isValid();

        $html = $wizard->valuesAsHtml();

        // Every visible page contributes a vf__page-header row.
        $this->assertStringContainsString('vf__page-header', $html);
        $this->assertStringContainsString('Step One', $html);
        $this->assertStringContainsString('Step Two', $html);
        $this->assertStringContainsString('Robin', $html);
        $this->assertStringContainsString('Amsterdam', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    #[Test]
    public function valuesAsHtmlSkipsPagesHiddenByVisibleCondition(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'Step One');
        $trigger = $wizard->addField('trigger', 'Trigger', ValidForm::VFORM_STRING);
        $page2 = $wizard->addPage('step-2', 'Step Two');
        $wizard->addField('city', 'City', ValidForm::VFORM_STRING);

        // Page 2 becomes invisible when 'trigger' equals 'hide'.
        $page2->addCondition('visible', false, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'hide'],
        ]);

        $_REQUEST['vf__dispatch'] = 'signup';
        $_REQUEST['trigger'] = 'hide';
        $wizard->isValid();

        $html = $wizard->valuesAsHtml();

        $this->assertStringContainsString('Step One', $html);
        $this->assertStringNotContainsString('Step Two', $html);
    }

    #[Test]
    public function valuesAsHtmlReturnsNoValuesMessageWhenWizardIsEmpty(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->setNoValuesMessage('Nothing submitted yet.');

        $html = $wizard->valuesAsHtml();

        $this->assertStringContainsString('Nothing submitted yet.', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    #[Test]
    public function valuesAsHtmlReturnsEmptyStringWithoutPagesAndMessage(): void
    {
        $wizard = new ValidWizard('signup');

        $this->assertSame('', $wizard->valuesAsHtml());
    }

    // --------------------------------------------------------------
    // serialize / unserialize
    // --------------------------------------------------------------

    #[Test]
    public function unserializeRestoresWizardAndKeepsOriginalUniqueId(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $wizard->addField('name', 'Name', ValidForm::VFORM_STRING);
        $originalId = $wizard->getUniqueId();

        $restored = ValidWizard::unserialize($wizard->serialize(false));

        $this->assertInstanceOf(ValidWizard::class, $restored);
        $this->assertSame('signup', $restored->getName());
        $this->assertSame($originalId, $restored->getUniqueId());
    }

    #[Test]
    public function unserializeOverridesUniqueIdWhenProvided(): void
    {
        // SECURITY NOTE: ValidForm::unserialize() (which this delegates to)
        // calls PHP's native unserialize() on the decoded payload without an
        // 'allowed_classes' whitelist. Feeding it attacker-controlled data
        // (e.g. from a cookie or hidden field) enables PHP object injection.
        // Serialized form state must never round-trip through the client.
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');

        $restored = ValidWizard::unserialize($wizard->serialize(false), 'override-id');

        $this->assertInstanceOf(ValidWizard::class, $restored);
        $this->assertSame('override-id', $restored->getUniqueId());
    }

    // --------------------------------------------------------------
    // toJs
    // --------------------------------------------------------------

    #[Test]
    public function toJsRendersLabelsClassesInitialPageAndCustomJs(): void
    {
        // SECURITY NOTE: next/previous labels and classes are interpolated
        // into the generated javascript without escaping. These values are
        // developer-supplied meta (not user input), but a value containing
        // a single quote would break out of the JS string literal.
        $wizard = new ValidWizard('signup', null, null, [
            'nextLabel' => 'Go',
            'previousLabel' => 'Back',
            'nextClass' => 'btn-next',
            'previousClass' => 'btn-prev',
        ]);
        $wizard->addPage('step-1');
        $wizard->addPage('step-2');
        $wizard->addPage('step-3');
        $wizard->setCurrentPage(3);
        $wizard->addConfirmPage();

        $js = $wizard->toJs("customWizardHook();");

        $this->assertStringContainsString("objForm.setLabel('next', 'Go');", $js);
        $this->assertStringContainsString("objForm.setLabel('previous', 'Back');", $js);
        $this->assertStringContainsString("objForm.setClass('next', 'btn-next');", $js);
        $this->assertStringContainsString("objForm.setClass('previous', 'btn-prev');", $js);
        // Current page > 1 is passed to the client as initialPage.
        $this->assertStringContainsString('"initialPage":3', $js);
        $this->assertStringContainsString('"confirmPage":true', $js);
        $this->assertStringContainsString('customWizardHook();', $js);
    }

    #[Test]
    public function toJsOmitsInitialPageAndClassesByDefault(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');

        $js = $wizard->toJs();

        $this->assertStringNotContainsString('initialPage', $js);
        $this->assertStringNotContainsString('setClass', $js);
        $this->assertStringContainsString('"confirmPage":false', $js);
    }

    // --------------------------------------------------------------
    // getInvalidFieldsUntil
    // --------------------------------------------------------------

    #[Test]
    public function getInvalidFieldsUntilCollectsErrorsAtAllNestingLevels(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'Step One');

        // Level 1: plain required field directly in a fieldset.
        $wizard->addField('plain', 'Plain', ValidForm::VFORM_STRING, ['required' => true]);

        // Level 2: required field inside an area.
        $area = $wizard->addArea('My area');
        $area->addField('area-field', 'Area field', ValidForm::VFORM_STRING, ['required' => true]);

        // Level 3: required field inside a multifield inside an area.
        $multi = $area->addMultiField('Combo');
        $multi->addField('mf-sub', ValidForm::VFORM_STRING, ['required' => true]);

        // Fields on the target page itself must NOT be validated.
        $wizard->addPage('step-2', 'Step Two');
        $wizard->addField('late-field', 'Late', ValidForm::VFORM_STRING, ['required' => true]);

        $errors = $wizard->getInvalidFieldsUntil('step-2');

        $names = array_map(static fn(array $error): string => array_key_first($error), $errors);
        $this->assertSame(['plain', 'area-field', 'mf-sub'], $names);

        // Every entry carries the validator's error message.
        foreach ($errors as $error) {
            $this->assertNotSame('', current($error));
        }
    }

    // --------------------------------------------------------------
    // getFields — nested traversal
    // --------------------------------------------------------------

    #[Test]
    public function getFieldsIncludesMultiFieldItselfWhenRequested(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $multi = $wizard->addMultiField('Full name');
        $multi->addField('first', ValidForm::VFORM_STRING);
        $multi->addField('last', ValidForm::VFORM_STRING);

        $fields = iterator_to_array($wizard->getFields(true));

        $this->assertContains($multi, $fields);
        $names = array_map(static fn($field) => $field->getName(), $fields);
        $this->assertContains('first', $names);
        $this->assertContains('last', $names);
    }

    #[Test]
    public function getFieldsTraversesMultiFieldNestedInsideArea(): void
    {
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $area = $wizard->addArea('My area');
        $multi = $area->addMultiField('Combo');
        $multi->addField('combo-a', ValidForm::VFORM_STRING);

        $names = [];
        foreach ($wizard->getFields() as $field) {
            $names[] = $field->getName();
        }

        // The third-level field is reached through Area > MultiField.
        $this->assertContains('combo-a', $names);
    }

    #[Test]
    public function getFieldsAddsMultiFieldAgainForNestedSubFieldsWithFields(): void
    {
        // KNOWN QUIRK: when a MultiField contains a sub-element that itself
        // has fields, getFields(true) adds the OUTER multifield a second time
        // (the inner one is never added). This is not reachable through the
        // public addField API — we inject directly into the collection to
        // pin down the traversal behavior.
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1');
        $multi = $wizard->addMultiField('Outer');
        $multi->addField('outer-a', ValidForm::VFORM_STRING);

        $inner = new MultiField('Inner');
        $inner->addField('inner-a', ValidForm::VFORM_STRING);
        $multi->getFields()->addObject($inner);

        $fields = iterator_to_array($wizard->getFields(true));

        $occurrences = count(array_keys($fields, $multi, true));
        $this->assertSame(2, $occurrences);

        $names = array_map(static fn($field) => $field->getName(), $fields);
        $this->assertContains('inner-a', $names);
    }

    #[Test]
    public function getFieldsAddsFieldlessPageChildrenAsIs(): void
    {
        // KNOWN QUIRK: the hasFields() fallback for page children is dead
        // code through the public API — Page::addField() wraps everything in
        // a Fieldset and Fieldset::hasFields() unconditionally returns true.
        // Injecting a bare element into the page's live collection pins down
        // the defensive branch: the child is added to the result as-is.
        $wizard = new ValidWizard('signup');
        $page = $wizard->addPage('step-1');
        $loose = new Hidden('loose', ValidForm::VFORM_STRING);
        $page->getFields()->addObject($loose);

        $fields = iterator_to_array($wizard->getFields());

        $this->assertContains($loose, $fields);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlEscapesSubmittedFieldValues(): void
    {
        // Field values rendered by valuesAsHtml() pass through
        // htmlspecialchars(ENT_QUOTES) in fieldAsHtml() — verify a submitted
        // XSS payload is neutralized in the overview table.
        $wizard = new ValidWizard('signup');
        $wizard->addPage('step-1', 'Step One');
        // VFORM_HTML is the only text type whose validation regex allows
        // angle brackets, so the payload survives validation.
        $wizard->addField('name', 'Name', ValidForm::VFORM_HTML);

        $_REQUEST['vf__dispatch'] = 'signup';
        $_REQUEST['name'] = '<script>alert(1)</script>';
        $wizard->isValid();

        $html = $wizard->valuesAsHtml();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    #[Test]
    public function isSubmittedBypassesCsrfUnlikeParentValidForm(): void
    {
        // SECURITY NOTE: ValidWizard::isSubmitted() does NOT call
        // CSRF::validate() — it returns true as soon as the dispatch key
        // matches. This differs from the parent ValidForm which checks CSRF
        // by default. The reason is pragmatic (wizard forms span multiple
        // pages and the CSRF flow is complex), but production deployments
        // should add their own CSRF check if using ValidWizard directly.
        $wizard = new ValidWizard('signup');
        $_REQUEST['vf__dispatch'] = 'signup';

        // No CSRF token in the request, but isSubmitted still returns true.
        $this->assertTrue($wizard->isSubmitted());
    }
}
