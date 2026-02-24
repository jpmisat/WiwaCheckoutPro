const fs = require('fs');
const content = fs.readFileSync('CHANGELOG.md', 'utf8');

const newEntry = `## [2.15.12] - ${new Date().toISOString().split('T')[0]}

### Fixed
- Hid the standard WooCommerce "Item removed. Undo?" notice on non-cart pages to avoid UI clutter when using the side cart and prevent unwanted redirects back to the cart page.

`;

// Insert the new entry before the first '## [X.Y.Z]' definition
const updatedContent = content.replace(/(## \[\d+\.\d+\.\d+\])/, newEntry + '$1');
fs.writeFileSync('CHANGELOG.md', updatedContent);
console.log('Changelog updated.');
