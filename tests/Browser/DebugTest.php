<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DebugTest extends DuskTestCase
{
    public function testStandardsPage(): void
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('http://opengrc.test/app/login')
                    ->waitForText('Sign in', 5)
                    ->type('#data\\.email', 'admin@example.com')
                    ->type('#data\\.password', 'password')
                    ->press('Sign in')
                    ->pause(3000);
                    
            // Navigate to standards page
            $browser->visit('http://opengrc.test/app/standards')
                    ->waitForText('Standards', 10)
                    ->screenshot('debug-standards-page');
                    
            $source = $browser->driver->getPageSource();
            
            // Look for create/new buttons
            preg_match_all('/<button[^>]*>([^<]*New[^<]*)<\/button>/', $source, $newButtons);
            preg_match_all('/<a[^>]*href="[^"]*create[^"]*"[^>]*>([^<]+)<\/a>/', $source, $createLinks);
            preg_match_all('/<.*?>(Create|New|Add).*?<\/.*?>/', $source, $createText);
            
            echo "New buttons found: " . count($newButtons[0]) . "\n";
            echo "Create links found: " . count($createLinks[0]) . "\n";
            echo "Create/New/Add text found: " . count($createText[0]) . "\n";
            
            if (count($newButtons[1]) > 0) {
                echo "New button text: " . implode(', ', $newButtons[1]) . "\n";
            }
            
            // Look for any button or link with create/new
            if (strpos($source, 'Create') !== false) echo "Page contains 'Create'\n";
            if (strpos($source, 'New') !== false) echo "Page contains 'New'\n";
            if (strpos($source, 'Add') !== false) echo "Page contains 'Add'\n";
            
            // Navigate to create form and debug field selectors
            $browser->clickLink('New Standard')
                    ->waitForText('Create Standard', 10)
                    ->pause(2000)
                    ->screenshot('create-form');
                    
            $source = $browser->driver->getPageSource();
            
            // Look for form field selectors
            preg_match_all('/input[^>]*id="([^"]*name[^"]*)"/', $source, $nameInputs);
            preg_match_all('/input[^>]*id="([^"]*code[^"]*)"/', $source, $codeInputs);
            preg_match_all('/input[^>]*id="([^"]*authority[^"]*)"/', $source, $authorityInputs);
            preg_match_all('/input[^>]*id="([^"]*url[^"]*)"/', $source, $urlInputs);
            
            echo "Name field IDs: " . implode(', ', $nameInputs[1]) . "\n";
            echo "Code field IDs: " . implode(', ', $codeInputs[1]) . "\n";  
            echo "Authority field IDs: " . implode(', ', $authorityInputs[1]) . "\n";
            echo "URL field IDs: " . implode(', ', $urlInputs[1]) . "\n";
            
            // Look for status dropdown
            preg_match_all('/select[^>]*/', $source, $selects);
            preg_match_all('/data-testid="[^"]*status[^"]*"/', $source, $statusTestIds);
            preg_match_all('/id="[^"]*status[^"]*"/', $source, $statusIds);
            
            echo "Select elements: " . count($selects[0]) . "\n";
            echo "Status test IDs: " . count($statusTestIds[0]) . "\n";
            echo "Status IDs: " . count($statusIds[0]) . "\n";
            
            if (count($statusIds[0]) > 0) {
                echo "Status IDs found: " . implode(', ', $statusIds[0]) . "\n";
            }
            
            // Look specifically for description/textarea fields
            preg_match_all('/textarea[^>]*id="([^"]*)"/', $source, $textareas);
            preg_match_all('/<[^>]*class="[^"]*tiptap[^"]*"/', $source, $tiptapElements);
            preg_match_all('/<[^>]*data-[^=]*editor[^>]*/', $source, $editorElements);
            preg_match_all('/id="([^"]*description[^"]*)"/', $source, $descriptionIds);
            
            echo "Textarea IDs: " . implode(', ', $textareas[1]) . "\n";
            echo "Tiptap elements: " . count($tiptapElements[0]) . "\n";
            echo "Editor elements: " . count($editorElements[0]) . "\n";
            echo "Description IDs: " . implode(', ', $descriptionIds[1]) . "\n";
            
            // Try to interact with description field
            try {
                $browser->type('#data\\.description', 'Test description text');
                echo "Successfully typed into #data.description\n";
            } catch (\Exception $e) {
                echo "Could not type into #data.description\n";
                
                // Try to find any contenteditable element
                preg_match_all('/contenteditable="true"/', $source, $contentEditables);
                echo "Contenteditable elements: " . count($contentEditables[0]) . "\n";
            }
            
            $browser->assertSee('Create Standard');
        });
    }
}