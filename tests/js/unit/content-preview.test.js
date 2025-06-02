/**
 * Unit tests for content-preview.js
 */

describe('MPAIContentPreview', () => {
  beforeEach(() => {
    // Load the content-preview.js script
    require('../../../assets/js/content-preview');
  });
  
  afterEach(() => {
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Clean up window.MPAIContentPreview
    delete window.MPAIContentPreview;
  });
  
  test('should export public API', () => {
    expect(window.MPAIContentPreview).toBeDefined();
    expect(window.MPAIContentPreview.createTextPreview).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.createImagePreview).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.createTablePreview).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.createCodePreview).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.createPreview).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.detectContentType).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.parseMarkdownTable).toBeInstanceOf(Function);
    expect(window.MPAIContentPreview.CONTENT_TYPES).toBeDefined();
  });
  
  test('should create a text preview with short content', () => {
    const content = 'This is a short text content';
    const preview = window.MPAIContentPreview.createTextPreview(content);
    
    expect(preview).toBeInstanceOf(HTMLDivElement);
    expect(preview.classList.contains('mpai-preview')).toBe(true);
    expect(preview.classList.contains('mpai-text-preview')).toBe(true);
    expect(preview.textContent).toBe(content);
    
    // Should not have show more/less button for short content
    expect(preview.querySelector('.mpai-preview-toggle')).toBeFalsy();
  });
  
  test('should create a text preview with long content and expandable option', () => {
    // Create long content that exceeds the default maxLength (300)
    let longContent = '';
    for (let i = 0; i < 30; i++) {
      longContent += 'This is a long text content that should be truncated. ';
    }
    
    const preview = window.MPAIContentPreview.createTextPreview(longContent, {
      maxLength: 100,
      expandable: true
    });
    
    expect(preview.classList.contains('mpai-text-preview')).toBe(true);
    
    // Should have collapsed and expanded content divs
    const collapsedContent = preview.querySelector('.mpai-preview-collapsed');
    const expandedContent = preview.querySelector('.mpai-preview-expanded');
    const toggleButton = preview.querySelector('.mpai-preview-toggle');
    
    expect(collapsedContent).toBeTruthy();
    expect(expandedContent).toBeTruthy();
    expect(toggleButton).toBeTruthy();
    
    // Collapsed content should be visible, expanded content hidden
    expect(collapsedContent.style.display).not.toBe('none');
    expect(expandedContent.style.display).toBe('none');
    
    // Collapsed content should be truncated
    expect(collapsedContent.textContent.length).toBeLessThanOrEqual(103); // 100 + '...'
    
    // Toggle button should say "Show more"
    expect(toggleButton.textContent).toBe('Show more');
    expect(toggleButton.getAttribute('aria-expanded')).toBe('false');
    
    // Click toggle button to expand
    toggleButton.click();
    
    // Now expanded content should be visible, collapsed content hidden
    expect(collapsedContent.style.display).toBe('none');
    expect(expandedContent.style.display).toBe('block');
    
    // Toggle button should say "Show less"
    expect(toggleButton.textContent).toBe('Show less');
    expect(toggleButton.getAttribute('aria-expanded')).toBe('true');
    
    // Click toggle button again to collapse
    toggleButton.click();
    
    // Back to collapsed state
    expect(collapsedContent.style.display).toBe('block');
    expect(expandedContent.style.display).toBe('none');
    expect(toggleButton.textContent).toBe('Show more');
    expect(toggleButton.getAttribute('aria-expanded')).toBe('false');
  });
  
  test('should create an image preview', () => {
    const src = 'https://example.com/image.jpg';
    const alt = 'Example image';
    
    const preview = window.MPAIContentPreview.createImagePreview(src, {
      alt,
      maxWidth: '200px',
      maxHeight: '150px'
    });
    
    expect(preview.classList.contains('mpai-image-preview')).toBe(true);
    
    const image = preview.querySelector('img');
    expect(image).toBeTruthy();
    expect(image.src).toBe(src);
    expect(image.alt).toBe(alt);
    expect(image.style.maxWidth).toBe('200px');
    expect(image.style.maxHeight).toBe('150px');
    expect(image.style.cursor).toBe('pointer');
    
    // Test click to expand (modal creation)
    image.click();
    
    const modal = document.querySelector('.mpai-image-modal');
    expect(modal).toBeTruthy();
    expect(modal.style.position).toBe('fixed');
    
    const expandedImage = modal.querySelector('img');
    expect(expandedImage).toBeTruthy();
    expect(expandedImage.src).toBe(src);
    
    const closeButton = modal.querySelector('button');
    expect(closeButton).toBeTruthy();
    
    // Test close button
    closeButton.click();
    expect(document.querySelector('.mpai-image-modal')).toBeFalsy();
  });
  
  test('should create a table preview', () => {
    const headers = ['Name', 'Age', 'Location'];
    const data = [
      ['John', '30', 'New York'],
      ['Jane', '25', 'Los Angeles'],
      ['Bob', '40', 'Chicago']
    ];
    
    const preview = window.MPAIContentPreview.createTablePreview(data, {
      headers,
      striped: true,
      bordered: true
    });
    
    expect(preview.classList.contains('mpai-table-preview')).toBe(true);
    
    const table = preview.querySelector('table');
    expect(table).toBeTruthy();
    expect(table.classList.contains('mpai-table')).toBe(true);
    expect(table.classList.contains('mpai-table-striped')).toBe(true);
    expect(table.classList.contains('mpai-table-bordered')).toBe(true);
    
    // Check headers
    const thead = table.querySelector('thead');
    expect(thead).toBeTruthy();
    
    const headerRow = thead.querySelector('tr');
    expect(headerRow).toBeTruthy();
    
    const headerCells = headerRow.querySelectorAll('th');
    expect(headerCells.length).toBe(3);
    expect(headerCells[0].textContent).toBe('Name');
    expect(headerCells[1].textContent).toBe('Age');
    expect(headerCells[2].textContent).toBe('Location');
    
    // Check data rows
    const tbody = table.querySelector('tbody');
    expect(tbody).toBeTruthy();
    
    const rows = tbody.querySelectorAll('tr');
    expect(rows.length).toBe(3);
    
    // Check first row
    const firstRowCells = rows[0].querySelectorAll('td');
    expect(firstRowCells.length).toBe(3);
    expect(firstRowCells[0].textContent).toBe('John');
    expect(firstRowCells[1].textContent).toBe('30');
    expect(firstRowCells[2].textContent).toBe('New York');
  });
  
  test('should create a table preview with expandable rows', () => {
    // Create more rows than the default maxRows (10)
    const data = [];
    for (let i = 0; i < 15; i++) {
      data.push([`Row ${i+1}`, `Value ${i+1}`]);
    }
    
    const preview = window.MPAIContentPreview.createTablePreview(data, {
      maxRows: 5,
      expandable: true
    });
    
    const table = preview.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    // Should only show maxRows initially
    expect(rows.length).toBe(5);
    
    // Should have a toggle button
    const toggleButton = preview.querySelector('.mpai-preview-toggle');
    expect(toggleButton).toBeTruthy();
    expect(toggleButton.textContent).toContain('Show 10 more rows');
    
    // Click toggle button to expand
    toggleButton.click();
    
    // Should now show all rows
    const expandedRows = tbody.querySelectorAll('tr');
    expect(expandedRows.length).toBe(15);
    
    // Toggle button text should change
    expect(toggleButton.getAttribute('aria-expanded')).toBe('true');
  });
  
  test('should create a code preview', () => {
    const code = 'function test() {\n  console.log("Hello world");\n}';
    
    const preview = window.MPAIContentPreview.createCodePreview(code, {
      language: 'javascript',
      lineNumbers: true
    });
    
    expect(preview.classList.contains('mpai-code-preview')).toBe(true);
    
    // Check header
    const header = preview.querySelector('.mpai-code-header');
    expect(header).toBeTruthy();
    
    const languageName = header.querySelector('.mpai-code-language');
    expect(languageName).toBeTruthy();
    expect(languageName.textContent).toBe('JavaScript');
    
    const copyButton = header.querySelector('.mpai-code-copy');
    expect(copyButton).toBeTruthy();
    
    // Check code content
    const codeWrapper = preview.querySelector('.mpai-code-wrapper');
    expect(codeWrapper).toBeTruthy();
    
    const codeElement = codeWrapper.querySelector('.mpai-code');
    expect(codeElement).toBeTruthy();
    
    // With line numbers enabled, should have line divs
    const codeLines = codeElement.querySelectorAll('.mpai-code-line');
    expect(codeLines.length).toBe(3); // 3 lines in our code
    
    // Each line should have a line number and content
    const firstLine = codeLines[0];
    expect(firstLine.querySelector('.mpai-code-line-number')).toBeTruthy();
    expect(firstLine.querySelector('.mpai-code-line-content')).toBeTruthy();
  });
  
  test('should detect code language based on content', () => {
    // JavaScript detection
    expect(window.MPAIContentPreview.detectCodeLanguage('function test() { return true; }')).toBe('javascript');
    
    // PHP detection
    expect(window.MPAIContentPreview.detectCodeLanguage('<?php echo "Hello"; ?>')).toBe('php');
    
    // HTML detection
    expect(window.MPAIContentPreview.detectCodeLanguage('<html><body>Hello</body></html>')).toBe('html');
    
    // CSS detection
    expect(window.MPAIContentPreview.detectCodeLanguage('.class { color: red; }')).toBe('css');
    
    // SQL detection
    expect(window.MPAIContentPreview.detectCodeLanguage('SELECT * FROM users WHERE id = 1')).toBe('sql');
    
    // JSON detection
    expect(window.MPAIContentPreview.detectCodeLanguage('{"name": "John", "age": 30}')).toBe('json');
    
    // Plaintext fallback
    expect(window.MPAIContentPreview.detectCodeLanguage('Just some plain text')).toBe('plaintext');
    
    // Use hint if provided
    expect(window.MPAIContentPreview.detectCodeLanguage('Some code', 'python')).toBe('python');
  });
  
  test('should detect content type based on content', () => {
    const CONTENT_TYPES = window.MPAIContentPreview.CONTENT_TYPES;
    
    // Image URL detection
    expect(window.MPAIContentPreview.detectContentType('https://example.com/image.jpg')).toBe(CONTENT_TYPES.IMAGE);
    expect(window.MPAIContentPreview.detectContentType('http://example.com/image.png?size=large')).toBe(CONTENT_TYPES.IMAGE);
    
    // Code detection
    expect(window.MPAIContentPreview.detectContentType('```javascript\nconst x = 1;\n```')).toBe(CONTENT_TYPES.CODE);
    expect(window.MPAIContentPreview.detectContentType('function test() { return true; }')).toBe(CONTENT_TYPES.CODE);
    expect(window.MPAIContentPreview.detectContentType('<?php echo "Hello"; ?>')).toBe(CONTENT_TYPES.CODE);
    
    // Table detection
    expect(window.MPAIContentPreview.detectContentType('| Name | Age |\n|------|-----|\n| John | 30  |')).toBe(CONTENT_TYPES.TABLE);
    
    // Text fallback
    expect(window.MPAIContentPreview.detectContentType('Just some plain text')).toBe(CONTENT_TYPES.TEXT);
  });
  
  test('should parse markdown table', () => {
    const tableContent = `
| Name | Age | Location |
|------|-----|----------|
| John | 30  | New York |
| Jane | 25  | LA       |
    `;
    
    const { headers, data } = window.MPAIContentPreview.parseMarkdownTable(tableContent);
    
    expect(headers).toEqual(['Name', 'Age', 'Location']);
    expect(data).toEqual([
      ['John', '30', 'New York'],
      ['Jane', '25', 'LA']
    ]);
  });
  
  test('should create a preview based on content type', () => {
    // Spy on the specific preview creation methods
    const spyTextPreview = jest.spyOn(window.MPAIContentPreview, 'createTextPreview');
    const spyImagePreview = jest.spyOn(window.MPAIContentPreview, 'createImagePreview');
    const spyCodePreview = jest.spyOn(window.MPAIContentPreview, 'createCodePreview');
    const spyTablePreview = jest.spyOn(window.MPAIContentPreview, 'createTablePreview');
    
    // Test text preview
    window.MPAIContentPreview.createPreview('This is plain text', { type: 'text' });
    expect(spyTextPreview).toHaveBeenCalled();
    
    // Test image preview
    window.MPAIContentPreview.createPreview('https://example.com/image.jpg', { type: 'image' });
    expect(spyImagePreview).toHaveBeenCalled();
    
    // Test code preview
    window.MPAIContentPreview.createPreview('```javascript\nconst x = 1;\n```', { type: 'code' });
    expect(spyCodePreview).toHaveBeenCalled();
    
    // Test table preview
    window.MPAIContentPreview.createPreview('| Name | Age |\n|------|-----|\n| John | 30  |', { type: 'table' });
    expect(spyTablePreview).toHaveBeenCalled();
    
    // Restore spies
    spyTextPreview.mockRestore();
    spyImagePreview.mockRestore();
    spyCodePreview.mockRestore();
    spyTablePreview.mockRestore();
  });
  
  test('should add preview styles to document', () => {
    // The styles should be added to the document head
    const styleElements = document.head.querySelectorAll('style');
    let previewStyleFound = false;
    
    for (let i = 0; i < styleElements.length; i++) {
      if (styleElements[i].textContent.includes('.mpai-preview')) {
        previewStyleFound = true;
        break;
      }
    }
    
    expect(previewStyleFound).toBe(true);
  });
});