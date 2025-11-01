#!/bin/bash

# Teamleader SDK - Laravel Pint Auto-Fix Script
# This script automatically fixes style violations in the specified files

echo "🔧 Teamleader SDK - Laravel Pint Auto-Fix"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if vendor/bin/pint exists
if [ ! -f "vendor/bin/pint" ]; then
    echo -e "${RED}❌ Error: vendor/bin/pint not found${NC}"
    echo "Please run: composer install"
    exit 1
fi

# Files that need fixing based on the test results
FILES=(
    "src/Resources/Deals/Deals.php"
    "src/Resources/Deals/Sources.php"
    "src/Resources/Files/Files.php"
    "src/Resources/General/Departments.php"
    "src/Resources/General/Teams.php"
    "src/Resources/General/Users.php"
    "src/Resources/General/WorkTypes.php"
    "src/Resources/Invoicing/Subscriptions.php"
    "src/Resources/Projects/LegacyMilestones.php"
)

echo "📋 Files to fix:"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✓ $file"
    else
        echo -e "   ${YELLOW}⚠ $file (not found)${NC}"
    fi
done
echo ""

# Option 1: Fix all files at once
echo "Option 1: Fix all files at once (recommended)"
echo "Option 2: Fix files individually"
echo "Option 3: Just run full Pint (fixes entire codebase)"
echo ""
read -p "Choose option (1-3): " choice

case $choice in
    1)
        echo ""
        echo "🔨 Fixing all specified files..."
        for file in "${FILES[@]}"; do
            if [ -f "$file" ]; then
                echo "   Fixing: $file"
                ./vendor/bin/pint "$file"
            fi
        done
        ;;
    2)
        echo ""
        echo "🔨 Fixing files individually..."
        for file in "${FILES[@]}"; do
            if [ -f "$file" ]; then
                echo ""
                echo "──────────────────────────────────────"
                echo "File: $file"
                read -p "Fix this file? (y/n): " fix
                if [ "$fix" = "y" ] || [ "$fix" = "Y" ]; then
                    ./vendor/bin/pint "$file"
                fi
            fi
        done
        ;;
    3)
        echo ""
        echo "🔨 Running Pint on entire codebase..."
        ./vendor/bin/pint
        ;;
    *)
        echo -e "${RED}Invalid option${NC}"
        exit 1
        ;;
esac

echo ""
echo "──────────────────────────────────────"
echo ""
echo "✅ Fixing complete!"
echo ""
echo "🧪 Running tests to verify..."
./vendor/bin/pint --test

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✨ All style issues fixed successfully!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Review the changes: git diff"
    echo "2. Commit the fixes: git add . && git commit -m 'fix: resolve Laravel Pint style violations'"
    echo "3. Push to repository: git push"
else
    echo ""
    echo -e "${YELLOW}⚠️  Some issues may require manual review${NC}"
    echo ""
    echo "Common remaining issues:"
    echo "- Superfluous PHPDoc tags (may need manual removal)"
    echo "- Complex indentation scenarios"
    echo ""
    echo "Review the Pint output above and fix remaining issues manually."
fi

echo ""
