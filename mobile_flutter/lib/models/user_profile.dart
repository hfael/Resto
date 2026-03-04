class UserProfile {
  const UserProfile({
    required this.id,
    required this.username,
    required this.email,
    required this.role,
  });

  final int id;
  final String username;
  final String email;
  final String role;

  bool get isAdmin => role == 'admin';

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: (json['id'] as num?)?.toInt() ?? 0,
      username: (json['username'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      role: (json['role'] ?? 'user') as String,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'username': username,
      'email': email,
      'role': role,
    };
  }
}
