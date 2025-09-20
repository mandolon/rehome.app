<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use App\Models\Project;
use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏗️ Seeding preconstruction platform demo data...');

        // Create first demo account - Construction Company
        $constructionAccount = Account::firstOrCreate([
            'slug' => 'apex-construction'
        ], [
            'name' => 'Apex Construction Group',
            'plan' => 'enterprise',
            'settings' => [
                'timezone' => 'America/New_York',
                'default_language' => 'en',
                'billing_address' => '123 Builder Ave, Construction City, CC 12345',
            ],
        ]);

        // Create second demo account - Development Firm
        $developmentAccount = Account::firstOrCreate([
            'slug' => 'urban-development'
        ], [
            'name' => 'Urban Development Partners',
            'plan' => 'pro',
            'settings' => [
                'timezone' => 'America/Los_Angeles',
                'default_language' => 'en',
                'billing_address' => '456 Development Dr, Los Angeles, CA 90210',
            ],
        ]);

        // Create users for construction account
        $admin = User::firstOrCreate([
            'email' => 'admin@apex-construction.com'
        ], [
            'account_id' => $constructionAccount->id,
            'name' => 'Sarah Chen',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $teamMember = User::firstOrCreate([
            'email' => 'team@apex-construction.com'
        ], [
            'account_id' => $constructionAccount->id,
            'name' => 'Mike Rodriguez',
            'password' => Hash::make('password'),
            'role' => 'team',
        ]);

        // Create client user for development account
        $client = User::firstOrCreate([
            'email' => 'client@urban-dev.com'
        ], [
            'account_id' => $developmentAccount->id,
            'name' => 'Jennifer Park',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        // Create realistic preconstruction projects
        $projects = [
            [
                'account_id' => $constructionAccount->id,
                'name' => 'Downtown Mixed-Use Development',
                'description' => '8-story mixed-use building with retail ground floor and residential units above. Located in the urban core with complex zoning requirements.',
                'phase' => 'Permitting',
                'zoning' => 'MU-3 Mixed Use High Density',
                'user_id' => $admin->id,
            ],
            [
                'account_id' => $constructionAccount->id,
                'name' => 'Riverside Residential Complex',
                'description' => 'Multi-family residential development featuring 24 townhomes with sustainable design elements and waterfront access.',
                'phase' => 'Design Development',
                'zoning' => 'R-4 Multi-Family Residential',
                'user_id' => $teamMember->id,
            ],
            [
                'account_id' => $developmentAccount->id,
                'name' => 'Tech Campus Expansion',
                'description' => 'Corporate office expansion including parking structures, landscaping, and utility infrastructure for a major tech company.',
                'phase' => 'Schematic Design',
                'zoning' => 'C-2 Commercial Office',
                'user_id' => $client->id,
            ],
        ];

        $createdProjects = [];
        foreach ($projects as $projectData) {
            $project = Project::firstOrCreate([
                'name' => $projectData['name'],
                'account_id' => $projectData['account_id'],
            ], [
                'account_id' => $projectData['account_id'],
                'description' => $projectData['description'],
                'phase' => $projectData['phase'],
                'zoning' => $projectData['zoning'],
                'metadata' => [
                    'created_by_seeder' => true,
                    'demo_data' => true,
                    'project_type' => 'Commercial Development',
                    'estimated_value' => rand(500000, 5000000),
                ],
            ]);
            $createdProjects[] = $project;
        }

        // Create sample documents with realistic content
        $this->createSampleDocuments($createdProjects);

        $this->command->info('✅ Demo data created successfully!');
        $this->command->info('');
        $this->command->info('📋 Account Information:');
        $this->command->info('  • Apex Construction Group (ID: ' . $constructionAccount->id . ')');
        $this->command->info('  • Urban Development Partners (ID: ' . $developmentAccount->id . ')');
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('  Admin:  admin@apex-construction.com / password');
        $this->command->info('  Team:   team@apex-construction.com / password');
        $this->command->info('  Client: client@urban-dev.com / password');
        $this->command->info('');
        $this->command->info('🏗️ Projects Created:');
        foreach ($createdProjects as $project) {
            $this->command->info('  • ' . $project->name . ' (' . $project->phase . ')');
        }
    }

    private function createSampleDocuments(array $projects): void
    {
        $this->command->info('📄 Creating sample documents...');

        // Sample document templates with realistic preconstruction content
        $documentTemplates = [
            [
                'name' => 'Zoning Requirements Summary.pdf',
                'content' => 'ZONING COMPLIANCE SUMMARY

Project: Mixed-Use Development
Zone: MU-3 Mixed Use High Density

SETBACK REQUIREMENTS:
- Front setback: Minimum 15 feet from property line
- Rear setback: Minimum 20 feet
- Side setbacks: Minimum 10 feet each side

HEIGHT RESTRICTIONS:
- Maximum building height: 8 stories or 96 feet
- Mechanical penthouses: Additional 15 feet permitted

PARKING REQUIREMENTS:
- Residential units: 1.5 spaces per unit minimum
- Retail space: 1 space per 300 sq ft
- Visitor parking: 0.25 spaces per residential unit

DENSITY:
- Maximum 60 units per acre
- Ground floor retail required on street-facing facades
- Minimum 15% open space requirement

PERMITS REQUIRED:
- Special Use Permit for mixed-use development
- Building permit for construction
- Occupancy permit for each unit/space

For questions contact City Planning Department at (555) 123-4567.',
                'mime_type' => 'application/pdf',
            ],
            [
                'name' => 'Building Code Analysis.txt',
                'content' => 'BUILDING CODE COMPLIANCE ANALYSIS

PROJECT: Riverside Residential Complex
CODE: 2021 International Building Code (IBC)

CONSTRUCTION TYPE: Type V-A Wood Frame
OCCUPANCY GROUP: R-3 One and Two-Family Dwellings

FIRE SAFETY REQUIREMENTS:
- Automatic sprinkler systems required in all units
- Smoke detectors in all bedrooms and hallways
- Fire-rated assemblies: 1-hour between units
- Emergency exits: Two means of egress per unit

ACCESSIBILITY COMPLIANCE:
- ADA accessible routes to all units
- 5% of units fully accessible (Type A)
- 2% of units with hearing/vision features (Type B)
- Accessible parking spaces: 1 per 25 spaces

STRUCTURAL REQUIREMENTS:
- Seismic design category D provisions
- Wind load: 110 mph basic wind speed
- Snow load: 25 psf ground snow load
- Foundation: Spread footings on engineered fill

ENERGY CODE COMPLIANCE:
- Insulation: R-20 walls, R-38 ceiling
- Windows: U-factor 0.30 maximum
- HVAC: 16 SEER minimum cooling equipment
- Water heating: 0.67 energy factor minimum

PERMIT SUBMISSION REQUIREMENTS:
- Architectural plans (11 sets)
- Structural calculations and drawings
- MEP plans and load calculations
- Site plan with utilities
- Soils report and foundation design',
                'mime_type' => 'text/plain',
            ],
            [
                'name' => 'Environmental Impact Assessment.md',
                'content' => '# Environmental Impact Assessment

## Project: Tech Campus Expansion

### Executive Summary
This Environmental Impact Assessment evaluates the potential environmental effects of the proposed Tech Campus Expansion project located at 789 Innovation Boulevard.

### Project Description
- **Total Area:** 15.5 acres
- **Building Footprint:** 125,000 sq ft office space
- **Parking:** 400 spaces in structured parking
- **Landscaping:** 35% of site area

### Environmental Considerations

#### Air Quality
- Construction emissions will be mitigated through dust control measures
- Operational impacts minimal due to office use
- Electric vehicle charging stations included (20% of spaces)

#### Water Resources
- Stormwater management through bioretention areas
- Low-impact development (LID) techniques implemented
- 30% reduction in runoff compared to conventional development

#### Traffic Impact
- Peak hour analysis shows acceptable Level of Service
- Transportation Demand Management plan included
- Public transit access within 0.25 miles

#### Noise Assessment
- Construction noise limited to daytime hours (7 AM - 6 PM)
- Operational noise below municipal thresholds
- Sound barriers along residential property lines

### Mitigation Measures
1. Native plant landscaping to support local wildlife
2. Bird-friendly building design features
3. Construction waste recycling program (75% diversion goal)
4. Energy-efficient building systems (LEED Gold target)

### Regulatory Compliance
- CEQA compliance documentation complete
- Air Quality Management District permits obtained
- Regional Water Quality Control Board approval pending

**Prepared by:** Environmental Solutions Inc.
**Date:** March 15, 2024',
                'mime_type' => 'text/markdown',
            ],
        ];

        foreach ($projects as $index => $project) {
            $template = $documentTemplates[$index % count($documentTemplates)];
            
            // Create storage path
            $storagePath = sprintf(
                'demo-documents/%d/%d/%s',
                $project->account_id,
                $project->id,
                $template['name']
            );

            // Store the document content
            Storage::disk('local')->put($storagePath, $template['content']);

            // Create document record
            $document = Document::create([
                'project_id' => $project->id,
                'account_id' => $project->account_id,
                'original_name' => $template['name'],
                'storage_path' => $storagePath,
                'mime_type' => $template['mime_type'],
                'size' => strlen($template['content']),
                'status' => 'completed',
                'metadata' => [
                    'uploaded_by_seeder' => true,
                    'processed_at' => now()->toISOString(),
                    'chunk_count' => 3,
                    'total_tokens' => ceil(strlen($template['content']) / 3.5),
                ],
            ]);

            // Create document chunks with embeddings for RAG testing
            $this->createDocumentChunks($document, $template['content']);
        }
    }

    private function createDocumentChunks(Document $document, string $content): void
    {
        // Split content into chunks of roughly 900 tokens
        $sentences = explode('. ', $content);
        $chunks = [];
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . $sentence) > 3000 && !empty($currentChunk)) { // ~900 tokens
                $chunks[] = $currentChunk;
                $currentChunk = $sentence . '.';
            } else {
                $currentChunk .= ($currentChunk ? '. ' : '') . $sentence . '.';
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        // Create document chunks with mock embeddings
        foreach ($chunks as $index => $chunkContent) {
            DocumentChunk::create([
                'account_id' => $document->account_id,
                'document_id' => $document->id,
                'content' => trim($chunkContent),
                'embedding' => $this->generateMockEmbedding(),
                'chunk_index' => $index,
                'token_count' => ceil(strlen($chunkContent) / 3.5),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'chunk_size' => strlen($chunkContent),
                    'embedding_model' => 'text-embedding-3-small',
                    'demo_data' => true,
                ],
            ]);
        }
    }

    private function generateMockEmbedding(): array
    {
        // Generate a mock 1536-dimensional embedding vector for demo purposes
        $embedding = [];
        for ($i = 0; $i < 1536; $i++) {
            $embedding[] = (rand(-1000, 1000) / 1000); // Random float between -1 and 1
        }
        return $embedding;
    }
}
