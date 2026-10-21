<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * buildCitations(): APA 7, MLA 9 and Chicago (bibliography) entries for books, each by its own rules —
 * author lists, initials, title capitals, place, and an organisation that is also the publisher.
 */
class CitationFormatsTest extends TestCase
{
    use RefreshDatabase;

    /** A record: 245 $a/$b, 260 $a and 008 language in the MARC data; the rest on the OPAC row. */
    private function cite(array $title, array $row, ?string $place = null, string $language = 'eng'): array
    {
        $fields = [['marctag' => ['code' => '245'], 'subfield' => array_map(fn ($code, $value) => ['code' => "245\$$code", 'value' => $value], array_keys($title), $title)],
                   ['marctag' => ['code' => '008'], 'subfield' => [['code' => '008$35-37', 'value' => $language]]]];
        if ($place) $fields[] = ['marctag' => ['code' => '260'], 'subfield' => [['code' => '260$a', 'value' => $place]]];

        $material = DB::table('materials')->insertGetId(['name' => 'Book ' . uniqid()]);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => json_encode($fields), 'is_published' => 1]);

        return buildCitations((object) ($row + ['catalog_id' => $id, 'title_statement' => implode(' ', $title)]));
    }

    public function test_an_organisation_that_is_also_the_publisher_is_named_once_except_in_chicago()
    {
        $this->assertSame([
            'APA'     => 'Food and Nutrition Research Institute - Department of Science and Technology. (2009). <em>2008 annual report</em>.',
            'MLA'     => '<em>2008 Annual Report</em>. Department of Science and Technology - Food and Nutrition Research Institute, 2009.',
            'Chicago' => 'Food and Nutrition Research Institute - Department of Science and Technology. <em>2008 Annual Report</em>. '
                       . 'Taguig City, Philippines: Department of Science and Technology - Food and Nutrition Research Institute, 2009.',
        ], $this->cite(['a' => '2008 annual report'], [
            'joint_authors' => 'Food and Nutrition Research Institute - Department of Science and Technology',
            'publisher' => 'Department of Science and Technology - Food and Nutrition Research Institute,', 'pub_year' => '2009',
        ], 'Taguig City, Philippines :'));
    }

    public function test_three_authors()
    {
        $this->assertSame([
            'APA'     => 'Fabian, E. F., Payumo, F. M., &amp; Reyes, P. M. (1997). <em>Development and storage study of deep-fried high-protein crunchies</em>. Philippine Journal of Nutrition.',
            'MLA'     => 'Fabian, E. F., et al. <em>Development and Storage Study of Deep-Fried High-Protein Crunchies</em>. Philippine Journal of Nutrition, 1997.',
            'Chicago' => 'Fabian, E. F., F. M. Payumo, and P. M. Reyes. <em>Development and Storage Study of Deep-Fried High-Protein Crunchies</em>. Philippine Journal of Nutrition, 1997.',
        ], $this->cite(['a' => 'Development and storage study of deep-fried high-protein crunchies'], [
            'joint_authors' => 'Fabian, E. F. / Payumo, F. M. / Reyes, P. M.', 'publisher' => 'Philippine Journal of Nutrition, ', 'pub_year' => '1997',
        ]));
    }

    public function test_initials_subtitle_and_a_supplied_date()
    {
        $this->assertSame([
            'APA'     => 'Tan, R. C., &amp; de Groot, L. C. P. G. M. (1960). <em>Dietary patterns of older Filipinos: Nutrisyon ng kabataan</em>. FNRI.',
            'MLA'     => 'Tan, Robby Carlo, and Lisette CPGM de Groot. <em>Dietary Patterns of Older Filipinos: Nutrisyon ng Kabataan</em>. FNRI, [1960].',
            'Chicago' => 'Tan, Robby Carlo, and Lisette CPGM de Groot. <em>Dietary Patterns of Older Filipinos: Nutrisyon ng Kabataan</em>. FNRI, [1960].',
        ], $this->cite(['a' => 'Dietary patterns of older Filipinos :', 'b' => 'nutrisyon ng kabataan'], [
            'joint_authors' => 'Tan, Robby Carlo / de Groot, Lisette CPGM', 'publisher' => 'FNRI,', 'pub_year' => '[1960]',
        ]));
    }

    public function test_no_author_no_date_and_a_title_not_in_english()
    {
        $this->assertSame([
            'APA'     => '<em>Gabay sa wastong nutrisyon</em>. (n.d.). FNRI.',
            'MLA'     => '<em>Gabay sa wastong nutrisyon</em>. FNRI.',
            'Chicago' => '<em>Gabay sa wastong nutrisyon</em>. FNRI, n.d.',
        ], $this->cite(['a' => 'Gabay sa wastong nutrisyon'], ['joint_authors' => '', 'publisher' => 'FNRI', 'pub_year' => null], null, 'fil'));
    }

    public function test_an_edition_a_question_and_markup_characters()
    {
        $this->assertSame([
            'APA'     => 'Cruz, J. (2001). <em>Fats &amp; oils: Are they safe?</em> (2nd ed.). FNRI.',
            'MLA'     => 'Cruz, Juan. <em>Fats &amp; Oils: Are They Safe?</em> 2nd ed., FNRI, 2001.',
            'Chicago' => 'Cruz, Juan. <em>Fats &amp; Oils: Are They Safe?</em> 2nd ed. Manila: FNRI, 2001.',
        ], $this->cite(['a' => 'Fats & oils: are they safe?'], [
            'joint_authors' => 'Cruz, Juan', 'publisher' => 'FNRI', 'pub_year' => '2001', 'edition' => '2nd ed',
        ], 'Manila :'));
    }

    public function test_two_organisations_take_no_comma_before_and()
    {
        $c = $this->cite(['a' => 'Seminar series'], [
            'joint_authors' => 'Food and Nutrition Research Institute / Nutrition Foundation of the Philippines', 'publisher' => 'FNRI', 'pub_year' => '2007',
        ]);

        $this->assertStringStartsWith('Food and Nutrition Research Institute, &amp; Nutrition Foundation of the Philippines. (2007).', $c['APA']);
        $this->assertStringStartsWith('Food and Nutrition Research Institute and Nutrition Foundation of the Philippines. <em>', $c['MLA']);
        $this->assertStringStartsWith('Food and Nutrition Research Institute and Nutrition Foundation of the Philippines. <em>', $c['Chicago']);
    }
}
