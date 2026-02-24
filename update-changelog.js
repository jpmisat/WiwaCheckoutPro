const fs = require('fs');
const content = fs.readFileSync('CHANGELOG.md', 'utf8');

const newEntry = `## [2.15.11] - ${new Date().toISOString().split('T')[0]}

### Fixed
- Fixed mobile booking button matching widths (Reservar vs Agregar).
- Replaced missing CSS variables in the success modal close button with literal hex colors to ensure the intended design is applied.
- Prevented JetPopup and Elementor popup close buttons from overlapping modal titles on mobile devices by adding appropriate padding.

`;

// Insert the new entry before the first '## [X.Y.Z]' definition
const updatedContent = content.replace(/(## \[\d+\.\d+\.\d+\])/, newEntry + '$1');
fs.writeFileSync('CHANGELOG.md', updatedContent);
console.log('Changelog updated.');
