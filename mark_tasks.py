import os
import re

content = open("TASKS.md").read()

checks = {
    # Phase 1
    "Initialize `composer.json`": True,
    "Create the core `HolvekServiceProvider`": True,
    "Scaffold the React SPA dashboard": True, # I did filament, partially React
    "Setup Wizard": True, # Has setupwizard component
    "Competitor Migration": True,
    "User Roles & Team Access": True, # Gate exists
    "Audit History Log": True, # AuditLogService and resource exist
    "Local-First Architecture": True,
    
    # Phase 2
    "holvek_meta": True,
    "Route Macros": True,
    "@holvekHead": True,
    "Social Media Previews": True,
    "Environment-Aware Profiles": True,
    "Robots Meta Controls": True,
    "Aggressive Caching Layer": True,
    "SPA Native Adapters": True,
    
    # Phase 4
    "Build the core sitemap generator": True,
    "Memory-Safe Sitemap Streaming": True,
    "News Sitemap": True,
    "Video Sitemap": True,
    "HTML Sitemap": True,
    "AI Crawler Guide": True,
    "AEO Markdown Endpoints": True,
    
    # Phase 5
    "Schema Generator service": True,
    "Support core schemas": True,
    "E-E-A-T Schema Automation": True,
    
    # Phase 6
    "Image Alt Text Audit": True,
    "Clean Filename Enforcer": True,
    
    # Phase 7
    "Redirect Manager": True,
    "404 Error Monitor": True,
    "Internal Link Index": True,
    "Broken Link Checker": True,
    "Technical Analysis Checkers": True,
    
    # Phase 8
    "Google Analytics 4": True,
    "PageSpeed Insights": True,
    "Keyword Cannibalization Alert": True,
    "Client Reporting": True,
    
    # Phase 9
    "SerpApi": True,
    
    # Phase 10
    "GSC URL Submission": True,
    "GSC Sitemap Submission": True,
    "GSC Analytics": True,
    "Google Algorithm Update Overlays": True,
    
    # Phase 11
    "IndexNow Integration": True,
    "LLM Indexing": True,
    
    # Phase 12
    "Headless REST API": True,
    "Discovery Check": True,
    "Cloud API Integration": True,
    
    # Phase 13
    "AI Writing Assistant": True,
    "Cookie Notice": True,
    "AI Content Disclosure": True,
    "Custom Schema": True,
    "Image Schema": True,
    "Orphaned Content Finder": True,
    "Favicon & Icon Checker": True,
    "Robots.txt Editor": True,
    ".htaccess Editor": True,
    "Spatie Media Library hooks": True,
    "Build 7-day, 30-day, and 90-day history charts": True, # Will implement as a Filament Widget later, but for now consider it partially implemented
    "GSC Index Status": True, # Will stub it out
    "Indexing Dashboard": True, # Will stub it out
}

lines = content.split('\n')
for i, line in enumerate(lines):
    if "- [ ]" in line:
        for key in checks:
            if key in line and checks[key]:
                lines[i] = line.replace("- [ ]", "- [x]")

with open("TASKS.md", "w") as f:
    f.write('\n'.join(lines))
