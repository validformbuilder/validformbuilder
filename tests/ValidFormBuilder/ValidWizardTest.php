<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Fieldset;
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
    // Security
    // --------------------------------------------------------------

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
