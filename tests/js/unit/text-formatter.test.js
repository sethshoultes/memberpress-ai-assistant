/**
 * Unit tests for text-formatter.js
 */

describe('MPAITextFormatter', () => {
  beforeEach(() => {
    // Load the text-formatter.js script
    require('../../../assets/js/text-formatter');
  });
  
  afterEach(() => {
    // Clean up window.MPAITextFormatter
    delete window.MPAITextFormatter;
  });
  
  test('should export public API', () => {
    expect(window.MPAITextFormatter).toBeDefined();
    expect(window.MPAITextFormatter.formatText).toBeInstanceOf(Function);
    expect(window.MPAITextFormatter.formatPlainText).toBeInstanceOf(Function);
    expect(window.MPAITextFormatter.autoLink).toBeInstanceOf(Function);
    expect(window.MPAITextFormatter.truncateText).toBeInstanceOf(Function);
    expect(window.MPAITextFormatter.escapeHtml).toBeInstanceOf(Function);
    expect(window.MPAITextFormatter.TOKEN_TYPES).toBeDefined();
  });
  
  test('should escape HTML special characters', () => {
    const input = '<div class="test">This is a test & it has "quotes" and \'apostrophes\'</div>';
    const expected = '&lt;div class=&quot;test&quot;&gt;This is a test &amp; it has &quot;quotes&quot; and &#039;apostrophes&#039;&lt;/div&gt;';
    
    const result = window.MPAITextFormatter.escapeHtml(input);
    
    expect(result).toBe(expected);
  });
  
  test('should format plain text with line breaks', () => {
    const input = 'Line 1\nLine 2\nLine 3';
    const expected = 'Line 1<br>Line 2<br>Line 3';
    
    const result = window.MPAITextFormatter.formatPlainText(input, false);
    
    expect(result).toBe(expected);
  });
  
  test('should auto-link URLs in text', () => {
    const input = 'Visit https://example.com for more info';
    const result = window.MPAITextFormatter.autoLink(input);
    
    expect(result).toContain('<a href="https://example.com"');
    expect(result).toContain('class="mpai-link"');
    expect(result).toContain('>https://example.com</a>');
  });
  
  test('should auto-link email addresses in text', () => {
    const input = 'Contact info@example.com for support';
    const result = window.MPAITextFormatter.autoLink(input);
    
    expect(result).toContain('<a href="mailto:info@example.com"');
    expect(result).toContain('class="mpai-link"');
    expect(result).toContain('>info@example.com</a>');
  });
  
  test('should truncate text to specified length', () => {
    const input = 'This is a very long text that should be truncated';
    const result = window.MPAITextFormatter.truncateText(input, 20);
    
    expect(result.length).toBeLessThanOrEqual(20);
    expect(result).toBe('This is a very long...');
  });
  
  test('should not truncate text if shorter than specified length', () => {
    const input = 'Short text';
    const result = window.MPAITextFormatter.truncateText(input, 20);
    
    expect(result).toBe(input);
  });
  
  test('should format markdown headings', () => {
    const input = '# Heading 1\n## Heading 2\n### Heading 3';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<h3 class="mpai-heading">Heading 1</h3>');
    expect(result).toContain('<h4 class="mpai-heading">Heading 2</h4>');
    expect(result).toContain('<h5 class="mpai-heading">Heading 3</h5>');
  });
  
  test('should format markdown bold text', () => {
    const input = 'This is **bold** text and this is also __bold__';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<strong>bold</strong>');
    expect(result).toContain('<strong>bold</strong>');
  });
  
  test('should format markdown italic text', () => {
    const input = 'This is *italic* text and this is also _italic_';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<em>italic</em>');
    expect(result).toContain('<em>italic</em>');
  });
  
  test('should format markdown links', () => {
    const input = 'This is a [link](https://example.com)';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<a href="https://example.com" class="mpai-link" target="_blank">link</a>');
  });
  
  test('should format markdown code blocks', () => {
    const input = '```javascript\nconst x = 1;\nconsole.log(x);\n```';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<pre class="mpai-code-block">');
    expect(result).toContain('<code');
    expect(result).toContain('const x = 1;');
    expect(result).toContain('console.log(x);');
  });
  
  test('should format markdown inline code', () => {
    const input = 'This is `inline code`';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<code class="mpai-code-inline">inline code</code>');
  });
  
  test('should format markdown unordered lists', () => {
    const input = '- Item 1\n- Item 2\n- Item 3';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<ul class="mpai-unordered-list">');
    expect(result).toContain('<li class="mpai-list-item">Item 1</li>');
    expect(result).toContain('<li class="mpai-list-item">Item 2</li>');
    expect(result).toContain('<li class="mpai-list-item">Item 3</li>');
    expect(result).toContain('</ul>');
  });
  
  test('should format markdown ordered lists', () => {
    const input = '1. Item 1\n2. Item 2\n3. Item 3';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<ol class="mpai-ordered-list">');
    expect(result).toContain('<li class="mpai-list-item">Item 1</li>');
    expect(result).toContain('<li class="mpai-list-item">Item 2</li>');
    expect(result).toContain('<li class="mpai-list-item">Item 3</li>');
    expect(result).toContain('</ol>');
  });
  
  test('should format markdown blockquotes', () => {
    const input = '> This is a blockquote\n> With multiple lines';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<blockquote class="mpai-blockquote">');
    expect(result).toContain('This is a blockquote');
    expect(result).toContain('With multiple lines');
    expect(result).toContain('</blockquote>');
  });
  
  test('should format markdown horizontal rules', () => {
    const input = 'Text above\n---\nText below';
    const result = window.MPAITextFormatter.formatText(input);
    
    expect(result).toContain('<hr class="mpai-hr">');
  });
  
  test('should handle empty input', () => {
    expect(window.MPAITextFormatter.formatText('')).toBe('');
    expect(window.MPAITextFormatter.formatPlainText('')).toBe('');
    expect(window.MPAITextFormatter.autoLink('')).toBe('');
    expect(window.MPAITextFormatter.truncateText('')).toBe('');
  });
  
  test('should add formatting styles to document', () => {
    // The styles should be added to the document head
    const styleElements = document.head.querySelectorAll('style');
    let formattingStyleFound = false;
    
    for (let i = 0; i < styleElements.length; i++) {
      if (styleElements[i].textContent.includes('.mpai-heading')) {
        formattingStyleFound = true;
        break;
      }
    }
    
    expect(formattingStyleFound).toBe(true);
  });
});