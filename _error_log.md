MPAI: Found potential match with jsonBlockRegex {"tool": "wp_cli", "parameters": {"command": "wp user list"}}
chat-interface.js?ver=1.5.4:357 MPAI: Valid tool call found with jsonBlockRegex {tool: 'wp_cli', parameters: {…}}
chat-interface.js?ver=1.5.4:330 MPAI: Found potential match with anyCodeBlockRegex {"tool": "wp_cli", "parameters": {"command": "wp user list"}}
chat-interface.js?ver=1.5.4:357 MPAI: Valid tool call found with anyCodeBlockRegex {tool: 'wp_cli', parameters: {…}}
chat-interface.js?ver=1.5.4:373 MPAI: Skipping duplicate tool call from anyCodeBlockRegex
chat-interface.js?ver=1.5.4:330 MPAI: Found potential match with indentedJsonBlockRegex {"tool": "wp_cli", "parameters": {"command": "wp user list"}}
chat-interface.js?ver=1.5.4:357 MPAI: Valid tool call found with indentedJsonBlockRegex {tool: 'wp_cli', parameters: {…}}
chat-interface.js?ver=1.5.4:373 MPAI: Skipping duplicate tool call from indentedJsonBlockRegex
chat-interface.js?ver=1.5.4:330 MPAI: Found potential match with multilineToolRegex undefined
chat-interface.js?ver=1.5.4:334 MPAI: Skipping empty match from multilineToolRegex
chat-interface.js?ver=1.5.4:407 MPAI: Skipping direct JSON match that is part of a code block
chat-interface.js?ver=1.5.4:457 MPAI: Found 1 tool calls to process
chat-interface.js?ver=1.5.4:468 MPAI: Tool call detection pattern statistics: {jsonBlockRegex: 1}
chat-interface.js?ver=1.5.4:472 MPAI: Tools being used: ['wp_cli']
mpai-logger.js?ver=1.5.4:174 23:23:08.845 MPAI [tool_usage]: Tool used: wp_cli {command: 'wp user list'}
load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2 
            
            
           POST http://localhost:10044/wp-admin/admin-ajax.php 400 (Bad Request)
send @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
ajax @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
e.<computed> @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
executeToolCall @ chat-interface.js?ver=1.5.4:585
(anonymous) @ chat-interface.js?ver=1.5.4:534
processToolCalls @ chat-interface.js?ver=1.5.4:511
success @ chat-interface.js?ver=1.5.4:111
c @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
fireWith @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
l @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
XMLHttpRequest.send
send @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
ajax @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
e.<computed> @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
sendMessage @ chat-interface.js?ver=1.5.4:91
(anonymous) @ chat-interface.js?ver=1.5.4:1504
dispatch @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
v.handle @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2Understand this errorAI
mpai-logger.js?ver=1.5.4:148 23:23:08.947 MPAI [tool_usage]: AJAX error executing tool wp_cli {xhr: {…}, status: 'error', error: 'Bad Request', tool: 'wp_cli', parameters: {…}, …}
MpaiLogger.error @ mpai-logger.js?ver=1.5.4:148
error @ chat-interface.js?ver=1.5.4:1041
c @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
fireWith @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
l @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
XMLHttpRequest.send
send @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
ajax @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
e.<computed> @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
executeToolCall @ chat-interface.js?ver=1.5.4:585
(anonymous) @ chat-interface.js?ver=1.5.4:534
processToolCalls @ chat-interface.js?ver=1.5.4:511
success @ chat-interface.js?ver=1.5.4:111
c @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
fireWith @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
l @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
XMLHttpRequest.send
send @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
ajax @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
(anonymous) @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
e.<computed> @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:5
sendMessage @ chat-interface.js?ver=1.5.4:91
(anonymous) @ chat-interface.js?ver=1.5.4:1504
dispatch @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2
v.handle @ load-scripts.php?c=0&load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,wp-hooks&ver=6.7.2:2Understand this errorAI